<?php

declare(strict_types=1);

namespace Tests\Modules\Facturas;

use App\Core\Validator;
use App\Modules\Facturas\Services\FacturaService;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Interfaces\FacturaRepositoryInterface;
use App\Shared\Interfaces\TransactionManagerInterface;
use PHPUnit\Framework\TestCase;

final class FacturaServiceTest extends TestCase
{
    public function testReceptionUpdatesEveryRelatedRecordInOneTransaction():void
    {
        $repository=new FakeFacturaRepository();$transactions=new FakeFacturaTransactions();$service=new FacturaService($repository,$transactions,new Validator());
        $result=$service->receive('BOD1',['id_pedido'=>7,'numero_factura'=>'001-10','fecha'=>date('Y-m-d'),'cantidad_recibida'=>5,'precio_unitario'=>2.5]);
        self::assertSame(['id'=>21,'number'=>'001-10'],$result);self::assertTrue($transactions->executed);self::assertSame(15.0,$repository->newStock);self::assertTrue($repository->inventoryMovement);self::assertTrue($repository->supplyMovement);self::assertTrue($repository->received);
    }

    public function testInvoiceNumberCannotRepeatForProvider():void
    {
        $repository=new FakeFacturaRepository();$repository->duplicateNumber=true;
        $this->expectException(ValidationException::class);
        (new FacturaService($repository,new FakeFacturaTransactions(),new Validator()))->receive('BOD1',['id_pedido'=>7,'numero_factura'=>'DUP','fecha'=>date('Y-m-d'),'cantidad_recibida'=>5,'precio_unitario'=>2]);
    }

    public function testOnlyRegisteredInvoiceCanBeReviewed():void
    {
        $repository=new FakeFacturaRepository();$repository->reviewable=false;
        $this->expectException(ValidationException::class);
        (new FacturaService($repository,new FakeFacturaTransactions(),new Validator()))->review(3,'aprobar_factura','ADM1');
    }

    public function testReceptionRejectsInvalidMonetaryPrecision():void
    {
        $this->expectException(ValidationException::class);
        (new FacturaService(new FakeFacturaRepository(),new FakeFacturaTransactions(),new Validator()))->receive('BOD1',[
            'id_pedido'=>7,'numero_factura'=>'001-11','fecha'=>date('Y-m-d'),
            'cantidad_recibida'=>'5.00','precio_unitario'=>'12.345'
        ]);
    }
}

final class FakeFacturaTransactions implements TransactionManagerInterface{public bool $executed=false;public function transaction(callable $operation):mixed{$this->executed=true;return $operation();}}
final class FakeFacturaRepository implements FacturaRepositoryInterface
{
    public bool $duplicateNumber=false;public float $newStock=0;public bool $inventoryMovement=false;public bool $supplyMovement=false;public bool $received=false;public bool $reviewable=true;
    public function schemaReady():bool{return true;}public function pendingOrders():array{return [];}public function supplies():array{return [];}public function recentByUser(string $userId):array{return [];}
    public function lockOrder(int $id):?array{return ['id_pedidos'=>$id,'id_proveedor'=>2,'id_insumo'=>4,'nombre_producto'=>'Abono','cantidad'=>5,'unidad_medida'=>'kg','estado'=>'Pendiente'];}
    public function invoiceExistsForOrder(int $orderId):bool{return false;}public function invoiceNumberExists(int $providerId,string $number):bool{return $this->duplicateNumber;}public function supplyNameExists(string $name):bool{return false;}
    public function createSupply(string $userId,string $name,string $type,?string $description,string $unit,?string $observations):int{return 4;}public function lockSupply(int $id):?array{return ['id_insumos'=>$id,'nombre'=>'Abono','tipo'=>'Fertilizantes','unidad_medida'=>'kg','cantidad'=>10];}
    public function linkOrderSupply(int $orderId,int $supplyId,string $name,string $unit):bool{return true;}public function createInvoice(int $orderId,int $providerId,string $userId,string $number,string $date,float $total,?string $observations):int{return 21;}
    public function createDetail(int $invoiceId,int $supplyId,string $name,string $unit,float $quantity,float $unitPrice,float $subtotal):int{return 31;}public function setSupplyStock(int $supplyId,float $stock):bool{$this->newStock=$stock;return true;}
    public function recordInventoryEntry(int $invoiceId,int $detailId,int $supplyId,string $userId,float $quantity,float $previous,float $new,string $observations):void{$this->inventoryMovement=true;}public function recordSupplyEntry(int $supplyId,string $userId,float $quantity,string $observations):void{$this->supplyMovement=true;}
    public function markOrderReceived(int $orderId):bool{$this->received=true;return true;}public function providers():array{return [];}public function findAll(array $filters):array{return [];}public function stats():array{return [];}
    public function review(int $invoiceId,string $status,string $reviewerId):bool{return $this->reviewable;}public function findDetailHeader(int $invoiceId):?array{return null;}public function findDetailItems(int $invoiceId):array{return [];}
}
