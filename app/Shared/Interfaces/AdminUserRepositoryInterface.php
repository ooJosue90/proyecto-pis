<?php

declare(strict_types=1);

namespace App\Shared\Interfaces;

interface AdminUserRepositoryInterface
{
    /** @return list<array<string,mixed>> */ public function findAll():array;
    public function emailExists(string $email,?string $excludeId=null):bool;
    public function idExists(string $id):bool;
    public function create(string $id,string $name,string $email,string $passwordHash,string $role):void;
    public function update(string $id,string $name,string $email,string $role,?string $passwordHash):bool;
    public function delete(string $id):bool;
}
