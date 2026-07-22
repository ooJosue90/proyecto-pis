<?php

declare(strict_types=1);

namespace App\Modules\Usuarios\Repositories;

use App\Core\Database;use App\Shared\Exceptions\DatabaseException;use App\Shared\Interfaces\AdminUserRepositoryInterface;use Throwable;

final class AdminUserRepository implements AdminUserRepositoryInterface
{
    public function __construct(private readonly Database $database){}
    public function findAll():array{try{$stmt=$this->database->connection()->prepare('SELECT id_usuario,nombre,email,rol,fecha_registro FROM usuarios ORDER BY id_usuario');$stmt->execute();$rows=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);$stmt->close();return $rows;}catch(Throwable $e){throw new DatabaseException(previous:$e);}}
    public function emailExists(string $email,?string $excludeId=null):bool{try{if($excludeId===null){$stmt=$this->database->connection()->prepare('SELECT COUNT(*) FROM usuarios WHERE email=?');$stmt->bind_param('s',$email);}else{$stmt=$this->database->connection()->prepare('SELECT COUNT(*) FROM usuarios WHERE email=? AND id_usuario<>?');$stmt->bind_param('ss',$email,$excludeId);}$stmt->execute();$exists=(int)$stmt->get_result()->fetch_row()[0]>0;$stmt->close();return $exists;}catch(Throwable $e){throw new DatabaseException(previous:$e);}}
    public function idExists(string $id):bool{try{$stmt=$this->database->connection()->prepare('SELECT COUNT(*) FROM usuarios WHERE id_usuario=?');$stmt->bind_param('s',$id);$stmt->execute();$exists=(int)$stmt->get_result()->fetch_row()[0]>0;$stmt->close();return $exists;}catch(Throwable $e){throw new DatabaseException(previous:$e);}}
    public function create(string $id,string $name,string $email,string $passwordHash,string $role):void{try{$stmt=$this->database->connection()->prepare('INSERT INTO usuarios(id_usuario,nombre,email,contrasena,rol,fecha_registro) VALUES(?,?,?,?,?,NOW())');$stmt->bind_param('sssss',$id,$name,$email,$passwordHash,$role);$stmt->execute();$stmt->close();}catch(Throwable $e){throw new DatabaseException('No se pudo crear el usuario.',$e);}}
    public function update(string $id,string $name,string $email,string $role,?string $passwordHash):bool{try{if($passwordHash!==null){$stmt=$this->database->connection()->prepare('UPDATE usuarios SET nombre=?,email=?,rol=?,contrasena=? WHERE id_usuario=?');$stmt->bind_param('sssss',$name,$email,$role,$passwordHash,$id);}else{$stmt=$this->database->connection()->prepare('UPDATE usuarios SET nombre=?,email=?,rol=? WHERE id_usuario=?');$stmt->bind_param('ssss',$name,$email,$role,$id);}$stmt->execute();$ok=$stmt->affected_rows===1||$this->idExists($id);$stmt->close();return $ok;}catch(Throwable $e){throw new DatabaseException('No se pudo actualizar el usuario.',$e);}}
    public function delete(string $id):bool{try{$stmt=$this->database->connection()->prepare('DELETE FROM usuarios WHERE id_usuario=?');$stmt->bind_param('s',$id);$stmt->execute();$ok=$stmt->affected_rows===1;$stmt->close();return $ok;}catch(Throwable $e){throw new DatabaseException('No se puede eliminar un usuario con registros relacionados.',$e);}}
}
