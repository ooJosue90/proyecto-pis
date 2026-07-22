<?php

declare(strict_types=1);

namespace Tests\Modules\Asistente;

use App\Modules\Asistente\Services\ContextBuilder;
use App\Modules\Asistente\Services\GeminiService;
use App\Modules\Asistente\Services\PermissionFilter;
use App\Shared\Interfaces\AssistantDataRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class AsistenteSecurityTest extends TestCase
{
    public function testFarmerCannotReadInventoryContext():void
    {
        $filter=new PermissionFilter();$analysis=$filter->analyze('Muéstrame el stock registrado en inventario');
        self::assertSame('internal',$analysis['category']);self::assertFalse($filter->authorized('Agricultor',$analysis));
    }
    public function testAgriculturalKnowledgeDoesNotLoadInternalData():void
    {
        $repository=new FakeAssistantRepository();$analysis=(new PermissionFilter())->analyze('¿Qué es el manejo integrado de plagas?');$context=(new ContextBuilder($repository))->build('Agricultor','USR1',$analysis);
        self::assertSame('general',$analysis['category']);self::assertSame(0,$repository->calls);self::assertStringContainsString('No se proporcionan datos internos',$context);
    }
    public function testContextRemovesSensitiveFieldsAndPassesAuthenticatedOwner():void
    {
        $repository=new FakeAssistantRepository();$context=(new ContextBuilder($repository,5,1000))->build('Agricultor','USR9',['category'=>'internal','topics'=>['cultivos']]);
        self::assertSame('USR9',$repository->userId);self::assertStringContainsString('Mango',$context);self::assertStringNotContainsString('secreto',$context);self::assertStringNotContainsString('password',$context);
    }
    public function testGeminiKeyCanRemainUnconfiguredWithoutExposure():void
    {
        $service=new GeminiService('','gemini-test');self::assertFalse($service->configured());
    }
}

final class FakeAssistantRepository implements AssistantDataRepositoryInterface
{
    public int $calls=0;public string $userId='';
    public function context(string $topic,string $role,string $userId,int $limit):array{$this->calls++;$this->userId=$userId;return [['tipo'=>'Mango','password'=>'secreto']];}
}
