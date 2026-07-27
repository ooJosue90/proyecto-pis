<?php
declare(strict_types=1);
namespace App\Modules\Inventario\Services;
use App\Core\Validator;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Interfaces\InventarioRepositoryInterface;
use App\Shared\Interfaces\TransactionManagerInterface;
final class InventarioService
{
    public function __construct(private readonly InventarioRepositoryInterface $repository,private readonly TransactionManagerInterface $transactions,private readonly Validator $validator){}
    public function all():array{return $this->repository->findAll();}
    /** @param array<string,mixed> $input */
    public function create(string $userId,array $input):int
    {
        $data=['nombre'=>trim((string)($input['nombre']??'')),'tipo'=>trim((string)($input['tipo']??'')),'descripcion'=>trim((string)($input['descripcion']??'')),'unidad_medida'=>trim((string)($input['unidad_medida']??'')),'cantidad'=>$input['cantidad']??null,'observaciones'=>trim((string)($input['observaciones']??''))];
        $this->validator->validate($data,['nombre'=>'required|max_length:200','tipo'=>'required|max_length:100','descripcion'=>'max_length:2000','unidad_medida'=>'required|max_length:50','cantidad'=>'required|numeric|min:0','observaciones'=>'max_length:2000']);
        return $this->transactions->transaction(function()use($userId,$data):int{$quantity=(float)$data['cantidad'];$id=$this->repository->create($userId,$data['nombre'],$data['tipo'],$data['descripcion']===''?null:$data['descripcion'],$data['unidad_medida'],$quantity,$data['observaciones']===''?null:$data['observaciones']);if($quantity>0){$this->repository->recordAdjustment($id,$userId,$quantity,0,$quantity,'Stock inicial');}return $id;});
    }
    /** @param array<string,mixed> $input */
    public function adjust(string $userId,array $input):void
    {
        $id=(int)($input['id_insumo']??0);$new=(float)($input['cantidad']??-1);$notes=trim((string)($input['observaciones']??''));if($id<=0||$new<0){throw new ValidationException(['inventario'=>['El insumo y la cantidad deben ser válidos.']]);}$this->transactions->transaction(function()use($id,$new,$userId,$notes):void{$item=$this->repository->lockByIdOrName($id,'');if($item===null){throw new ValidationException(['inventario'=>['El insumo no existe.']]);}$previous=$item['cantidad'];if(abs($new-$previous)<0.0001){return;}if(!$this->repository->setStock($id,$new)){throw new ValidationException(['inventario'=>['No se pudo actualizar el stock.']]);}$this->repository->recordAdjustment($id,$userId,$new-$previous,$previous,$new,$notes===''?null:$notes);});
    }
}
