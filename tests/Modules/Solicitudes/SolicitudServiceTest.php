<?php
declare(strict_types=1);
namespace Tests\Modules\Solicitudes;
use App\Modules\Solicitudes\Services\SolicitudService;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Interfaces\InventarioRepositoryInterface;
use App\Shared\Interfaces\SolicitudRepositoryInterface;
use App\Shared\Interfaces\TransactionManagerInterface;
use PHPUnit\Framework\TestCase;
final class SolicitudServiceTest extends TestCase
{
    public function testDeliveryUpdatesStockStateAndMovementAtomically():void
    {
        $requests=new FakeRequests();$inventory=new FakeInventory(20);$tx=new FakeRequestTransactions();
        (new SolicitudService($requests,$inventory,$tx))->process(5,'entregar','BOD1');
        self::assertTrue($tx->executed);self::assertTrue($inventory->decremented);self::assertTrue($inventory->movement);self::assertSame('Entregado',$requests->to);
    }
    public function testDeliveryRejectsInsufficientStock():void
    {
        $this->expectException(ValidationException::class);
        (new SolicitudService(new FakeRequests(),new FakeInventory(2),new FakeRequestTransactions()))->process(5,'entregar','BOD1');
    }
    public function testReviewOnlyAllowsKnownActions():void
    {
        $this->expectException(ValidationException::class);
        (new SolicitudService(new FakeRequests(),new FakeInventory(20),new FakeRequestTransactions()))->review(5,'eliminar');
    }
    public function testManualCreationMultipliesQuantityByHectares():void
    {
        $requests=new FakeRequests();$count=(new SolicitudService($requests,new FakeInventory(20),new FakeRequestTransactions()))->createManual('AGR1',['id_lote'=>2,'hectareas'=>2,'productos'=>[['id_insumo'=>3,'cantidad'=>4]]]);
        self::assertSame(1,$count);self::assertSame(8.0,$requests->createdQuantity);
    }
}
final class FakeRequestTransactions implements TransactionManagerInterface{public bool $executed=false;public function transaction(callable $operation):mixed{$this->executed=true;return $operation();}}
final class FakeRequests implements SolicitudRepositoryInterface
{
    public ?string $to=null;public ?float $createdQuantity=null;
    public function adminDashboard():array{return ['solicitudes'=>[],'stats_solicitudes'=>[]];}
    public function historyByUser(string $userId):array{return [];}
    public function lockInState(int $id,string $state):?array{return ['id_producto_solicitud'=>$id,'id_insumos'=>3,'nombre'=>'Abono','cantidad_solicitada'=>10,'estado'=>$state];}
    public function transition(int $id,string $from,string $to,?int $insumoId=null):bool{$this->to=$to;return true;}
    public function ownedLoteArea(int $loteId,string $userId):?float{return 5;}
    public function findInsumo(int $id):?array{return ['id_insumos'=>$id,'nombre'=>'Abono','tipo'=>'Fertilizante'];}
    public function create(string $userId,int $loteId,int $insumoId,string $stage,string $name,float $quantity,?string $observations):int{$this->createdQuantity=$quantity;return 1;}
}
final class FakeInventory implements InventarioRepositoryInterface
{
    public bool $decremented=false;public bool $movement=false;public function __construct(private readonly float $stock){}
    public function lockByIdOrName(?int $id,string $name):?array{return ['id_insumos'=>3,'cantidad'=>$this->stock];}
    public function decrementStock(int $id,float $quantity):bool{$this->decremented=true;return true;}
    public function recordDelivery(int $insumoId,string $userId,int $solicitudId,float $quantity):void{$this->movement=true;}
    public function findAll():array{return [];}
    public function create(string $userId,string $name,string $type,?string $description,string $unit,float $quantity,?string $observations):int{return 1;}
    public function setStock(int $id,float $quantity):bool{return true;}
    public function recordAdjustment(int $id,string $userId,float $change,float $previous,float $new,?string $observations):void{}
}
