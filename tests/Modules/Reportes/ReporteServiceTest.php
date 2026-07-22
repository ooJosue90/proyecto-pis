<?php

declare(strict_types=1);

namespace Tests\Modules\Reportes;

use App\Modules\Reportes\Services\ReporteService;
use App\Shared\Interfaces\ReporteRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class ReporteServiceTest extends TestCase
{
    public function testProcessedRequestMetricsAreCalculatedFromRepositoryData():void
    {
        $data=(new ReporteService(new FakeReporteRepository()))->processedRequests();
        self::assertSame(4,$data['solicitudMetricas']['total']);
        self::assertSame(2,$data['solicitudMetricas']['Entregado']);
        self::assertSame(1,$data['solicitudMetricas']['Rechazado']);
        self::assertSame(1,$data['solicitudMetricas']['Cancelado']);
    }

    public function testAdminDashboardCombinesAllReadModels():void
    {
        $data=(new ReporteService(new FakeReporteRepository()))->adminDashboard();
        self::assertSame(3,$data['total_usuarios']);self::assertCount(1,$data['usuarios_por_rol']);self::assertCount(1,$data['actividad']);
    }
}

final class FakeReporteRepository implements ReporteRepositoryInterface
{
    public function totals():array{return ['total_usuarios'=>3,'total_cultivos'=>2,'total_lotes'=>4];}
    public function usersByRole():array{return [['rol'=>'Administrador','cantidad'=>1]];}public function users():array{return [];}public function cropsByType():array{return [];}public function recentCrops():array{return [];}public function recentActivity():array{return [['tipo'=>'usuario']];}
    public function processedRequests():array{return [['estado'=>'Entregado'],['estado'=>'Entregado'],['estado'=>'Rechazado'],['estado'=>'Cancelado']];}public function invoiceProducts():array{return [];}
}
