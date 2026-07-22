<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Services;

use App\Modules\Dashboard\Repositories\DashboardRepository;
final class DashboardService
{
    public function __construct(private readonly DashboardRepository $repository){}
    public function admin():array{$data=$this->repository->admin();$m=array_map('intval',$data['metrics']);$events=[];$total=0;$e=$data['event_counts'];foreach([['solicitudes','solicitudes_fecha','fas fa-clipboard-check','Solicitudes pendientes',' solicitudes esperan aprobación.','#solicitudes'],['facturas','facturas_fecha','fas fa-receipt','Facturas registradas',' facturas están pendientes de revisión.','#facturas'],['pedidos','pedidos_fecha','fas fa-truck-fast','Pedidos recibidos',' pedidos fueron recibidos en los últimos 7 días.','#pedidos-proveedores'],['usuarios','usuarios_fecha','fas fa-user-check','Usuarios nuevos',' usuarios se registraron en los últimos 7 días.','#usuarios']] as [$key,$date,$icon,$title,$suffix,$target]){$count=(int)($e[$key]??0);if($count>0){$total+=$count;$events[]=['icon'=>$icon,'title'=>$title,'message'=>$count.$suffix,'date'=>$e[$date],'target'=>$target];}}foreach($data['notifications'] as $notification){$total++;$events[]=['icon'=>'fas fa-satellite-dish','title'=>'Actividad del sistema','message'=>$notification['mensaje'],'date'=>$notification['fecha'],'target'=>'#dashboard'];}usort($events,fn($a,$b)=>strtotime((string)$b['date'])<=>strtotime((string)$a['date']));$alerts=(int)$m['total_insumos_criticos']+(int)$m['total_productos_bajos'];$items=(int)$m['total_inventario_operativo']+$alerts;$days=['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];$months=['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];return array_merge($data,$m,['total_alertas_inventario'=>$alerts,'notification_total'=>$total,'system_events'=>$events,'last_activity'=>$data['last_activity'],'pending_requests_count'=>(int)($e['solicitudes']??0),'registered_invoices'=>['total'=>(int)($e['facturas']??0)],'received_orders'=>['total'=>(int)($e['pedidos']??0)],'total_inventory_items'=>$items,'inventory_health'=>$items>0?(int)round(((int)$m['total_inventario_operativo']/$items)*100):100,'admin_today'=>$days[(int)date('w')].', '.date('j').' de '.$months[(int)date('n')-1].' de '.date('Y')]);}
    public function warehouse():array{return $this->repository->warehouse();}
    public function farmer(string $userId): array
    {
        $data = $this->repository->farmer($userId);
        $stages = ['Siembra' => 0, 'Desarrollo' => 0, 'Cosecha' => 0, 'Sin etapa' => 0];
        foreach ($data['lotes'] as $lote) {
            $label = match ((int) ($lote['etapa_actual'] ?? 0)) {
                1 => 'Siembra',
                2 => 'Desarrollo',
                3 => 'Cosecha',
                default => 'Sin etapa',
            };
            $stages[$label]++;
        }

        $days = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
        $months = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

        return $data + [
            'total_lotes' => count($data['lotes']),
            'etapas' => $stages,
            'farmer_today' => $days[(int) date('w')] . ', ' . date('j') . ' de ' . $months[(int) date('n') - 1] . ' de ' . date('Y'),
        ];
    }
}
