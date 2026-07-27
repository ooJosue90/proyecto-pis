<?php

declare(strict_types=1);

namespace Tests\Modules\Usuarios;

use App\Core\Validator;use App\Modules\Usuarios\Services\AdminUserService;use App\Shared\Exceptions\ValidationException;use App\Shared\Interfaces\AdminUserRepositoryInterface;use PHPUnit\Framework\TestCase;

final class AdminUserServiceTest extends TestCase
{
    public function testCreatedPasswordIsHashed():void{$repo=new FakeAdminUserRepository();$id=(new AdminUserService($repo,new Validator()))->create(['cedula'=>'099','nombre'=>'Ana','email'=>'ana@example.com','contrasena'=>'secreto8','rol'=>'Agricultor']);self::assertSame('099',$id);self::assertNotSame('secreto8',$repo->hash);self::assertTrue(password_verify('secreto8',$repo->hash));}
    public function testInvalidRoleIsRejected():void{$this->expectException(ValidationException::class);(new AdminUserService(new FakeAdminUserRepository(),new Validator()))->create(['nombre'=>'Ana','email'=>'ana@example.com','contrasena'=>'secreto8','rol'=>'Superusuario']);}
    public function testAdministratorCannotDeleteOwnAccount():void{$this->expectException(ValidationException::class);(new AdminUserService(new FakeAdminUserRepository(),new Validator()))->delete('ADM1','ADM1');}
}
final class FakeAdminUserRepository implements AdminUserRepositoryInterface
{
    public string $hash='';public function findAll():array{return [];}public function emailExists(string $email,?string $excludeId=null):bool{return false;}public function idExists(string $id):bool{return false;}public function create(string $id,string $name,string $email,string $passwordHash,string $role):void{$this->hash=$passwordHash;}public function update(string $id,string $name,string $email,string $role,?string $passwordHash):bool{return true;}public function delete(string $id):bool{return true;}
}
