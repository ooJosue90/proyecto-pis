<?php

declare(strict_types=1);

namespace App\Modules\Usuarios\Services;

use App\Core\Validator;use App\Shared\Exceptions\ValidationException;use App\Shared\Interfaces\AdminUserRepositoryInterface;

final class AdminUserService
{
    private const ROLES=['Administrador','Agricultor','Bodeguero'];
    public function __construct(private readonly AdminUserRepositoryInterface $repository,private readonly Validator $validator){}
    public function dashboard():array{$users=$this->repository->findAll();$byRole=array_fill_keys(self::ROLES,0);$latest=null;foreach($users as $user){if(isset($byRole[$user['rol']])){$byRole[$user['rol']]++;}if(!empty($user['fecha_registro'])&&($latest===null||strtotime($user['fecha_registro'])>strtotime($latest))){$latest=$user['fecha_registro'];}}return ['usuarios_rows'=>$users,'usuarios_por_rol'=>$byRole,'total_usuarios'=>count($users),'ultimo_registro'=>$latest];}
    public function create(array $input):string{$data=$this->validate($input,true);$id=trim((string)($input['cedula']??''));if($id==='' ){$id='U'.date('Ymd').strtoupper(bin2hex(random_bytes(3)));}if($this->repository->idExists($id)){throw new ValidationException(['cedula'=>['La cédula ya está registrada.']]);}if($this->repository->emailExists($data['email'])){throw new ValidationException(['email'=>['El correo ya está registrado.']]);}$this->repository->create($id,$data['nombre'],$data['email'],password_hash($data['password'],PASSWORD_DEFAULT),$data['rol']);return $id;}
    public function update(array $input):void{$id=trim((string)($input['id_usuario']??''));if($id===''){throw new ValidationException(['usuario'=>['El usuario no es válido.']]);}$data=$this->validate($input,false);if($this->repository->emailExists($data['email'],$id)){throw new ValidationException(['email'=>['El correo ya está registrado en otro usuario.']]);}$hash=$data['password']===''?null:password_hash($data['password'],PASSWORD_DEFAULT);if(!$this->repository->update($id,$data['nombre'],$data['email'],$data['rol'],$hash)){throw new ValidationException(['usuario'=>['El usuario no existe.']]);}}
    public function delete(string $id,string $actorId):void{if($id===''||$id==='1'||hash_equals($actorId,$id)){throw new ValidationException(['usuario'=>['No se puede eliminar esta cuenta administrativa.']]);}if(!$this->repository->delete($id)){throw new ValidationException(['usuario'=>['El usuario no existe.']]);}}
    private function validate(array $input,bool $creating):array{$password=trim((string)($creating?($input['contrasena']??''):($input['nueva_contrasena']??'')));$data=['nombre'=>trim((string)($input['nombre']??'')),'email'=>trim((string)($input['email']??'')),'rol'=>trim((string)($input['rol']??'')),'password'=>$password];$rules=['nombre'=>'required|max_length:150','email'=>'required|email|max_length:100','rol'=>'required|in:'.implode(',',self::ROLES),'password'=>($creating?'required|':'').'max_length:255'];$this->validator->validate($data,$rules);if($password!==''&&mb_strlen($password)<6){throw new ValidationException(['password'=>['La contraseña debe tener al menos 6 caracteres.']]);}return $data;}
}
