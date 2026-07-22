<?php

declare(strict_types=1);

namespace App\Modules\Reportes\Services;

use App\Shared\Interfaces\ReporteRepositoryInterface;

final class ReporteService
{
    public function __construct(private readonly ReporteRepositoryInterface $repository){}
    public function adminDashboard():array{$totals=$this->repository->totals();return ['total_usuarios'=>$totals['total_usuarios']??0,'total_cultivos'=>$totals['total_cultivos']??0,'total_lotes'=>$totals['total_lotes']??0,'usuarios_por_rol'=>$this->repository->usersByRole(),'all_users'=>$this->repository->users(),'cultivos_por_tipo'=>$this->repository->cropsByType(),'cultivos_recientes'=>$this->repository->recentCrops(),'actividad'=>$this->repository->recentActivity()];}
    public function processedRequests():array{$requests=$this->repository->processedRequests();$metrics=['total'=>count($requests),'Entregado'=>0,'Rechazado'=>0,'Cancelado'=>0];foreach($requests as $request){$status=(string)($request['estado']??'');if(isset($metrics[$status])){$metrics[$status]++;}}return ['solicitudes'=>$requests,'solicitudMetricas'=>$metrics];}
    public function invoiceProducts():array{return ['productos_factura'=>$this->repository->invoiceProducts()];}
}
