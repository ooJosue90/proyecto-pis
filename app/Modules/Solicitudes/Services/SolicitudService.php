<?php
declare(strict_types=1);
namespace App\Modules\Solicitudes\Services;
use App\Shared\Domain\CultivationStage;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Interfaces\InventarioRepositoryInterface;
use App\Shared\Interfaces\SolicitudRepositoryInterface;
use App\Shared\Interfaces\TransactionManagerInterface;
final class SolicitudService
{
    public function __construct(private readonly SolicitudRepositoryInterface $requests,private readonly InventarioRepositoryInterface $inventory,private readonly TransactionManagerInterface $transactions){}
    public function review(int $id,string $action): void
    {
        $to=match($action){'aprobar'=>'Aprobado','rechazar'=>'Rechazado',default=>throw new ValidationException(['accion'=>['Acción no permitida.']])};
        if($id<=0||!$this->requests->transition($id,'Pendiente',$to)){throw new ValidationException(['solicitud'=>['La solicitud ya fue procesada o no existe.']]);}
    }
    public function adminDashboard(): array{return $this->requests->adminDashboard();}
    public function userHistory(string $userId): array
    {
        $requests=$this->requests->historyByUser($userId);$stats=['total'=>count($requests),'pendiente'=>0,'aprobado'=>0,'entregado'=>0,'cerrado'=>0];
        foreach($requests as $request){$state=strtolower(trim((string)($request['estado']??'')));if(isset($stats[$state])){$stats[$state]++;}elseif(in_array($state,['rechazado','cancelado'],true)){$stats['cerrado']++;}}
        return ['solicitudes'=>$requests,'historialStats'=>$stats];
    }
    public function process(int $id,string $action,string $userId): void
    {
        if($action==='cancelar'){if(!$this->requests->transition($id,'Aprobado','Cancelado')){throw new ValidationException(['solicitud'=>['Acción no permitida para el estado actual.']]);}return;}
        if($action!=='entregar'){throw new ValidationException(['accion'=>['Acción no permitida.']]);}
        $this->transactions->transaction(function()use($id,$userId):void{
            $request=$this->requests->lockInState($id,'Aprobado');
            if($request===null){throw new ValidationException(['solicitud'=>['La solicitud no está aprobada o ya fue procesada.']]);}
            $quantity=(float)$request['cantidad_solicitada'];
            $item=$this->inventory->lockByIdOrName(isset($request['id_insumos'])?(int)$request['id_insumos']:null,(string)$request['nombre']);
            if($quantity<=0||$item===null||$quantity>$item['cantidad']){throw new ValidationException(['inventario'=>['No existe stock suficiente para completar la entrega.']]);}
            if(!$this->inventory->decrementStock($item['id_insumos'],$quantity)||!$this->requests->transition($id,'Aprobado','Entregado',$item['id_insumos'])){throw new ValidationException(['solicitud'=>['La operación cambió mientras se procesaba.']]);}
            $this->inventory->recordDelivery($item['id_insumos'],$userId,$id,$quantity);
        });
    }
    /** @param array<string,mixed> $input */
    public function createManual(string $userId,array $input): int
    {
        $loteId=(int)($input['id_lote']??0);$hectares=(float)($input['hectareas']??0);$products=is_array($input['productos']??null)?$input['productos']:[];$observations=trim((string)($input['observaciones']??''));
        $area=$this->requests->ownedLoteArea($loteId,$userId);
        if($loteId<=0||$area===null||$hectares<=0||$hectares>$area){throw new ValidationException(['lote'=>['Las hectáreas deben ser válidas y no superar el área del lote.']]);}
        if($products===[]){throw new ValidationException(['productos'=>['Agregue al menos un insumo.']]);}
        return $this->transactions->transaction(function()use($products,$userId,$loteId,$hectares,$observations):int{$created=0;foreach($products as $product){if(!is_array($product))continue;$insumoId=(int)($product['id_insumo']??0);$perHectare=(float)($product['cantidad']??0);if($insumoId<=0||$perHectare<=0)continue;$insumo=$this->requests->findInsumo($insumoId);if($insumo===null)continue;$stage=CultivationStage::normalizeName($insumo['tipo']);$this->requests->create($userId,$loteId,$insumoId,$stage,$insumo['nombre'],$perHectare*$hectares,$observations===''?null:$observations);$created++;}if($created===0){throw new ValidationException(['productos'=>['No se encontró ningún insumo válido.']]);}return $created;});
    }
}
