<?php
declare(strict_types=1);
namespace Tests\Modules\Inventario;
use App\Core\Validator;use App\Modules\Inventario\Services\InventarioService;use App\Shared\Interfaces\InventarioRepositoryInterface;use App\Shared\Interfaces\TransactionManagerInterface;use PHPUnit\Framework\TestCase;
final class InventarioServiceTest extends TestCase
{
    public function testInitialStockCreatesAnAdjustmentMovement():void{$repo=new FakeInventoryRepository();$tx=new FakeInventoryTransactions();$id=(new InventarioService($repo,$tx,new Validator()))->create('BOD1',['nombre'=>'Abono','tipo'=>'Fertilizante','unidad_medida'=>'kg','cantidad'=>25]);self::assertSame(9,$id);self::assertTrue($tx->executed);self::assertSame(25.0,$repo->adjustment);}
    public function testAdjustmentCannotProduceNegativeStock():void{$this->expectException(\App\Shared\Exceptions\ValidationException::class);(new InventarioService(new FakeInventoryRepository(),new FakeInventoryTransactions(),new Validator()))->adjust('BOD1',['id_insumo'=>9,'cantidad'=>-1]);}
}
final class FakeInventoryTransactions implements TransactionManagerInterface{public bool $executed=false;public function transaction(callable $operation):mixed{$this->executed=true;return $operation();}}
final class FakeInventoryRepository implements InventarioRepositoryInterface
{
    public ?float $adjustment=null;public function findAll():array{return [];}public function create(string $userId,string $name,string $type,?string $description,string $unit,float $quantity,?string $observations):int{return 9;}public function lockByIdOrName(?int $id,string $name):?array{return ['id_insumos'=>9,'cantidad'=>10];}public function decrementStock(int $id,float $quantity):bool{return true;}public function recordDelivery(int $insumoId,string $userId,int $solicitudId,float $quantity):void{}public function setStock(int $id,float $quantity):bool{return true;}public function recordAdjustment(int $id,string $userId,float $change,float $previous,float $new,?string $observations):void{$this->adjustment=$change;}
}
