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
        self::assertSame('general',$analysis['category']);self::assertSame(0,$repository->calls);self::assertStringContainsString('No se proporcionaron datos',$context);self::assertStringContainsString('BASE TÉCNICA CURADA',$context);
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

    public function testNaturalInventoryCountIsDetectedAsInternal():void
    {
        $analysis=(new PermissionFilter())->analyze('¿Cuánto inventario queda?');
        self::assertSame('internal',$analysis['category']);
        self::assertSame('count',$analysis['operation']);
        self::assertSame(['inventario'],$analysis['topics']);
    }

    public function testLowStockUsesSpecificOperation():void
    {
        $analysis=(new PermissionFilter())->analyze('Muéstrame los insumos con stock bajo en inventario');
        self::assertSame('low_stock',$analysis['operation']);
        self::assertSame('internal',$analysis['category']);
    }

    public function testPersonalizedAdviceLoadsAgriculturalRecord():void
    {
        $repository=new FakeAssistantRepository();
        $analysis=(new PermissionFilter())->analyze('Dame recomendaciones para mi cultivo según su etapa actual');
        $context=(new ContextBuilder($repository))->build('Agricultor','USR2',$analysis);
        self::assertSame('internal',$analysis['category']);
        self::assertSame('advice',$analysis['operation']);
        self::assertContains('agricultura',$repository->topics);
        self::assertStringContainsString('BASE TÉCNICA CURADA',$context);
    }

    public function testFollowUpInheritsAuthorizedTopic():void
    {
        $filter=new PermissionFilter();
        $previous=$filter->analyze('Muéstrame mis solicitudes registradas');
        $analysis=$filter->analyze('¿Y cuáles están pendientes?',$previous);
        self::assertTrue($analysis['follow_up']);
        self::assertSame(['solicitudes'],$analysis['topics']);
        self::assertSame('pending',$analysis['operation']);
    }

    public function testActionsAreReadOnlyGuidanceAndRespectRoleTopics():void
    {
        $filter=new PermissionFilter();
        $ownAction=$filter->analyze('Registra una plaga');
        $forbiddenAction=$filter->analyze('Crea un usuario');
        self::assertSame('action',$ownAction['category']);
        self::assertTrue($filter->authorized('Agricultor',$ownAction));
        self::assertFalse($filter->authorized('Agricultor',$forbiddenAction));
    }

    public function testPeriodAndStatusArePassedToRepository():void
    {
        $repository=new FakeAssistantRepository();
        $analysis=(new PermissionFilter())->analyze('Muéstrame mis solicitudes pendientes de este mes');
        (new ContextBuilder($repository))->build('Agricultor','USR3',$analysis);
        self::assertSame('month',$repository->criteria['period']);
        self::assertSame('Pendiente',$repository->criteria['status']);
        self::assertSame('pending',$repository->criteria['operation']);
    }
}

final class FakeAssistantRepository implements AssistantDataRepositoryInterface
{
    public int $calls=0;public string $userId='';
    /** @var list<string> */
    public array $topics=[];
    /** @var array<string,mixed> */
    public array $criteria=[];
    public function context(string $topic,string $role,string $userId,int $limit,array $criteria=[]):array{$this->calls++;$this->userId=$userId;$this->topics[]=$topic;$this->criteria=$criteria;return [['tipo'=>'Mango','password'=>'secreto']];}
}
