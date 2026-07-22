<?php

declare(strict_types=1);

namespace App\Modules\Facturas\Services;

use App\Core\Validator;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Interfaces\FacturaRepositoryInterface;
use App\Shared\Interfaces\TransactionManagerInterface;

final class FacturaService
{
    public const SUPPLY_TYPES=['Fungicidas','Insecticidas','Herbicidas','Fertilizantes','Fertilizantes foliares','Coadyuvantes','Trampas','Equipos/Herramientas','Bioinsumos','Correctores de suelo','Herramientas agrícolas','Sistemas de riego','Materiales y suministros'];
    public function __construct(private readonly FacturaRepositoryInterface $repository,private readonly TransactionManagerInterface $transactions,private readonly Validator $validator){}
    public function schemaReady():bool{return $this->repository->schemaReady();}
    public function receptionDashboard(string $userId,int $selectedOrderId):array
    {
        if(!$this->schemaReady()){return ['tablesReady'=>false,'pedidosPendientes'=>[],'pedidoSeleccionado'=>null,'pedidoSeleccionadoId'=>$selectedOrderId,'insumosDisponibles'=>[],'facturasRecientes'=>[],'tiposInsumoPermitidos'=>self::SUPPLY_TYPES];}
        $orders=$this->repository->pendingOrders();$selected=null;foreach($orders as $order){if((int)$order['id_pedidos']===$selectedOrderId){$selected=$order;break;}}
        return ['tablesReady'=>$this->schemaReady(),'pedidosPendientes'=>$orders,'pedidoSeleccionado'=>$selected,'pedidoSeleccionadoId'=>$selectedOrderId,'insumosDisponibles'=>$this->repository->supplies(),'facturasRecientes'=>$this->repository->recentByUser($userId),'tiposInsumoPermitidos'=>self::SUPPLY_TYPES];
    }
    /** @param array<string,mixed> $input @return array{id:int,number:string} */
    public function receive(string $userId,array $input):array
    {
        if(!$this->schemaReady()){throw new ValidationException(['factura'=>['Primero ejecute actualizar_flujo_pedidos_facturas.sql en phpMyAdmin.']]);}
        $data=['order_id'=>(int)($input['id_pedido']??0),'number'=>trim((string)($input['numero_factura']??'')),'date'=>trim((string)($input['fecha']??'')),'quantity'=>$input['cantidad_recibida']??null,'price'=>$input['precio_unitario']??null,'supply_id'=>(int)($input['id_insumo']??0),'observations'=>trim((string)($input['observaciones']??''))];
        $this->validator->validate($data,['number'=>'required|max_length:60','date'=>'required|date','quantity'=>'required|numeric|min:0','price'=>'required|numeric|min:0','observations'=>'max_length:1000']);
        $data['quantity']=round((float)$data['quantity'],2);$data['price']=round((float)$data['price'],2);
        if($data['order_id']<=0||$data['quantity']<=0){throw new ValidationException(['factura'=>['Complete correctamente pedido, factura, fecha, cantidad y precio.']]);}
        return $this->transactions->transaction(function()use($userId,$input,$data):array{
            $order=$this->repository->lockOrder($data['order_id']);
            if($order===null){throw new ValidationException(['factura'=>['El pedido seleccionado no existe.']]);}
            if($order['estado']==='Cancelado'){throw new ValidationException(['factura'=>['No se puede registrar un comprobante para un pedido cancelado.']]);}
            if($order['estado']!=='Pendiente'){throw new ValidationException(['factura'=>['El pedido ya fue recibido o no está disponible.']]);}
            if($this->repository->invoiceExistsForOrder($data['order_id'])){throw new ValidationException(['factura'=>['Este pedido ya tiene un comprobante registrado.']]);}
            if($this->repository->invoiceNumberExists((int)$order['id_proveedor'],$data['number'])){throw new ValidationException(['factura'=>['El número de factura ya está registrado para este proveedor.']]);}
            $supplyId=(int)($order['id_insumo']?:$data['supply_id']);
            if(empty($order['id_insumo'])&&$supplyId===-1){$new=$this->newSupply($input);if($this->repository->supplyNameExists($new['name'])){throw new ValidationException(['factura'=>['Ya existe un producto con ese nombre. Selecciónelo en la lista.']]);}$supplyId=$this->repository->createSupply($userId,$new['name'],$new['type'],$new['description'],$new['unit'],$new['observations']);}
            if($supplyId<=0){throw new ValidationException(['factura'=>['Seleccione un producto de inventario válido.']]);}
            $supply=$this->repository->lockSupply($supplyId);if($supply===null){throw new ValidationException(['factura'=>['El producto relacionado ya no existe en el inventario.']]);}
            $unit=(string)($supply['unidad_medida']?:$order['unidad_medida']?:'unid');
            if(empty($order['id_insumo'])&&!$this->repository->linkOrderSupply($data['order_id'],$supplyId,(string)$supply['nombre'],$unit)){throw new ValidationException(['factura'=>['El pedido cambió de estado durante la recepción.']]);}
            $total=round($data['quantity']*$data['price'],2);$notes=$data['observations']===''?null:$data['observations'];
            $invoiceId=$this->repository->createInvoice($data['order_id'],(int)$order['id_proveedor'],$userId,$data['number'],$data['date'],$total,$notes);
            $detailId=$this->repository->createDetail($invoiceId,$supplyId,(string)$supply['nombre'],$unit,$data['quantity'],$data['price'],$total);
            $previous=(float)$supply['cantidad'];$newStock=round($previous+$data['quantity'],2);
            if(!$this->repository->setSupplyStock($supplyId,$newStock)){throw new ValidationException(['factura'=>['No se pudo actualizar el inventario.']]);}
            $movement="Entrada por factura {$data['number']}, pedido #{$data['order_id']}";
            $this->repository->recordInventoryEntry($invoiceId,$detailId,$supplyId,$userId,$data['quantity'],$previous,$newStock,$movement);
            $this->repository->recordSupplyEntry($supplyId,$userId,$data['quantity'],$movement);
            if(!$this->repository->markOrderReceived($data['order_id'])){throw new ValidationException(['factura'=>['El pedido cambió de estado durante la recepción.']]);}
            return ['id'=>$invoiceId,'number'=>$data['number']];
        });
    }
    public function adminDashboard(array $query):array
    {
        $filters=['id_proveedor'=>(int)($query['id_proveedor']??0),'estado'=>trim((string)($query['estado']??'')),'fecha_desde'=>$this->validDateOrEmpty($query['fecha_desde']??''),'fecha_hasta'=>$this->validDateOrEmpty($query['fecha_hasta']??'')];
        if(!$this->schemaReady()){return ['tablesReady'=>false,'proveedores'=>[],'facturas'=>[],'stats'=>['total_facturas'=>0,'total_monto'=>0,'total_aprobado'=>0,'registradas'=>0,'aprobadas'=>0,'rechazadas'=>0],'filtroProveedor'=>$filters['id_proveedor'],'filtroEstado'=>$filters['estado'],'fechaDesde'=>$filters['fecha_desde'],'fechaHasta'=>$filters['fecha_hasta'],'estadosValidos'=>['Registrada','Aprobada','Rechazada','Anulada']];}
        return ['tablesReady'=>$this->schemaReady(),'proveedores'=>$this->repository->providers(),'facturas'=>$this->repository->findAll($filters),'stats'=>$this->repository->stats(),'filtroProveedor'=>$filters['id_proveedor'],'filtroEstado'=>$filters['estado'],'fechaDesde'=>$filters['fecha_desde'],'fechaHasta'=>$filters['fecha_hasta'],'estadosValidos'=>['Registrada','Aprobada','Rechazada','Anulada']];
    }
    public function review(int $invoiceId,string $action,string $reviewerId):string
    {
        $status=match($action){'aprobar_factura'=>'Aprobada','rechazar_factura'=>'Rechazada',default=>throw new ValidationException(['factura'=>['Acción de revisión no permitida.']])};
        if($invoiceId<=0||!$this->repository->review($invoiceId,$status,$reviewerId)){throw new ValidationException(['factura'=>['Acción no permitida para el estado actual de la factura.']]);}
        return $status==='Aprobada'?'Factura aprobada.':'Factura rechazada.';
    }
    public function detail(int $invoiceId):array
    {
        if($invoiceId<=0||($invoice=$this->repository->findDetailHeader($invoiceId))===null){throw new ValidationException(['factura'=>['Factura no encontrada.']]);}
        return ['factura'=>$invoice,'detalles'=>$this->repository->findDetailItems($invoiceId),'estadoClase'=>strtolower((string)$invoice['estado'])];
    }
    private function validDateOrEmpty(mixed $value):string{$value=trim((string)$value);if($value===''){return '';}try{$this->validator->validate(['fecha'=>$value],['fecha'=>'date']);return $value;}catch(ValidationException){return '';}}
    private function newSupply(array $input):array{$data=['name'=>trim((string)($input['nuevo_insumo_nombre']??'')),'type'=>trim((string)($input['nuevo_insumo_tipo']??'')),'description'=>trim((string)($input['nuevo_insumo_descripcion']??'')),'unit'=>trim((string)($input['nuevo_insumo_unidad']??'')),'observations'=>trim((string)($input['nuevo_insumo_observaciones']??''))];$this->validator->validate($data,['name'=>'required|max_length:200','type'=>'required|in:'.implode(',',self::SUPPLY_TYPES),'description'=>'max_length:1000','unit'=>'required|max_length:50','observations'=>'max_length:500']);foreach(['description','observations'] as $key){$data[$key]=$data[$key]===''?null:$data[$key];}return $data;}
}
