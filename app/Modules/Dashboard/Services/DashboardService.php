<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Services;

use App\Core\Url;
use App\Modules\Dashboard\Repositories\DashboardRepository;
use App\Shared\Domain\CultivationStage;
use App\Shared\Support\ContextualMessage;

final class DashboardService
{
    public function __construct(private readonly DashboardRepository $repository){}
    public function admin():array{$data=$this->repository->admin();$m=array_map('intval',$data['metrics']);$events=[];$total=0;$e=$data['event_counts'];foreach([['solicitudes','solicitudes_fecha','fas fa-clipboard-check','Solicitudes pendientes',' solicitudes esperan aprobación.','#solicitudes'],['facturas','facturas_fecha','fas fa-receipt','Facturas registradas',' facturas están pendientes de revisión.','#facturas'],['pedidos','pedidos_fecha','fas fa-truck-fast','Pedidos recibidos',' pedidos fueron recibidos en los últimos 7 días.','#pedidos-proveedores'],['usuarios','usuarios_fecha','fas fa-user-check','Usuarios nuevos',' usuarios se registraron en los últimos 7 días.','#usuarios']] as [$key,$date,$icon,$title,$suffix,$target]){$count=(int)($e[$key]??0);if($count>0){$total+=$count;$events[]=['icon'=>$icon,'title'=>$title,'message'=>$count.$suffix,'date'=>$e[$date],'target'=>$target];}}foreach($data['notifications'] as $notification){$total++;$events[]=['icon'=>'fas fa-satellite-dish','title'=>'Actividad del sistema','message'=>$notification['mensaje'],'date'=>$notification['fecha'],'target'=>'#dashboard'];}usort($events,fn($a,$b)=>strtotime((string)$b['date'])<=>strtotime((string)$a['date']));$alerts=(int)$m['total_insumos_criticos']+(int)$m['total_productos_bajos'];$items=(int)$m['total_inventario_operativo']+$alerts;$pending=(int)($e['solicitudes']??0);$context=[];if($pending>0){$context[]=ContextualMessage::make('admin-pending-requests','warning','Solicitudes por revisar',"Hay {$pending} solicitud".($pending===1?'':'es').' esperando aprobación. Atiéndalas para que bodega pueda preparar la entrega.','Revisar solicitudes','#solicitudes','assignment_late');}if($alerts>0){$context[]=ContextualMessage::make('admin-stock-alerts','danger','Inventario requiere atención',"Hay {$alerts} producto".($alerts===1?'':'s').' con existencias bajas o agotadas. Revise el inventario general antes de continuar.','Ver inventario',Url::route('/inventario'),'inventory');}$days=['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];$months=['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];return array_merge($data,$m,['contextual_messages'=>$context,'total_alertas_inventario'=>$alerts,'notification_total'=>$total,'system_events'=>$events,'last_activity'=>$data['last_activity'],'pending_requests_count'=>$pending,'registered_invoices'=>['total'=>(int)($e['facturas']??0)],'received_orders'=>['total'=>(int)($e['pedidos']??0)],'total_inventory_items'=>$items,'inventory_health'=>$items>0?(int)round(((int)$m['total_inventario_operativo']/$items)*100):100,'admin_today'=>$days[(int)date('w')].', '.date('j').' de '.$months[(int)date('n')-1].' de '.date('Y')]);}
    public function warehouse():array
    {
        $data = $this->repository->warehouse();
        $context = [];
        $approved = (int) ($data['total_solicitudes_aprobadas'] ?? 0);
        $orders = (int) ($data['total_pedidos_pendientes'] ?? 0);
        $lowStock = count(array_filter(
            $data['insumos'] ?? [],
            static fn (array $item): bool => (float) ($item['cantidad'] ?? 0) <= 5
        ));
        if ($approved > 0) {
            $context[] = ContextualMessage::make('warehouse-approved-requests', 'warning', 'Entregas pendientes', "Hay {$approved} solicitud" . ($approved === 1 ? '' : 'es') . ' aprobada' . ($approved === 1 ? '' : 's') . ' lista' . ($approved === 1 ? '' : 's') . ' para preparar.', 'Ir a entregas', '#warehouse-approved-requests', 'outbox');
        }
        if ($orders > 0) {
            $context[] = ContextualMessage::make('warehouse-pending-orders', 'info', 'Compras por comprobar', "Hay {$orders} pedido" . ($orders === 1 ? '' : 's') . ' pendiente' . ($orders === 1 ? '' : 's') . ' de registrar con su factura.', 'Registrar comprobantes', '#warehouse-pending-orders', 'receipt_long');
        }
        if ($lowStock > 0) {
            $context[] = ContextualMessage::make('warehouse-low-stock', 'danger', 'Reposición recomendada', "Hay {$lowStock} insumo" . ($lowStock === 1 ? '' : 's') . ' con cinco unidades o menos.', 'Revisar inventario', Url::route('/inventario'), 'production_quantity_limits');
        }

        return $data + ['contextual_messages' => $context];
    }
    public function farmer(string $userId): array
    {
        $data = $this->repository->farmer($userId);
        $stages = array_fill_keys(array_values(CultivationStage::labels()), 0);
        foreach ($data['lotes'] as $lote) {
            $label = CultivationStage::label((int) ($lote['etapa_actual'] ?? CultivationStage::NONE));
            $stages[$label]++;
        }

        $days = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
        $months = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

        $context = [];
        if ($data['cultivos'] === []) {
            $context[] = ContextualMessage::make('farmer-first-crop', 'info', 'Comience por su cultivo', 'Registre el cultivo que desea gestionar. Después podrá asociarle un lote.', 'Ir a Cultivos', '#cultivo', 'eco');
        } elseif ($data['lotes'] === []) {
            $context[] = ContextualMessage::make('farmer-first-lot', 'info', 'El siguiente paso es crear un lote', 'Ya tiene un cultivo registrado. Ahora indique la ubicación y el área donde trabajará.', 'Ir a Lotes', '#lote', 'location_on');
            $context[] = ContextualMessage::make('farmer-lot-preparation', 'info', 'Prepare los datos del terreno', 'Tenga a mano la ubicación, el área y el cultivo que asignará al nuevo lote.', 'Completar lote aquí', '#lote', 'fact_check');
        } else {
            $shownLots = 0;
            foreach ($data['lotes'] as $lot) {
                $stage = (int) ($lot['etapa_actual'] ?? CultivationStage::NONE);
                $location = (string) ($lot['ubicacion'] ?? ('#' . $lot['id_lote']));
                if ($stage >= CultivationStage::HARVEST) {
                    if ($shownLots === 0) {
                        $context[] = ContextualMessage::make(
                            'farmer-lot-' . (int) $lot['id_lote'] . '-harvest-record',
                            'success',
                            'Cosecha lista para registrar',
                            "El lote {$location} llegó a Cosecha. Registre la producción cuando finalice el trabajo.",
                            'Ver lote en este panel',
                            '#lote',
                            'inventory_2'
                        );
                        $shownLots++;
                    }
                    continue;
                }
                $next = $stage === CultivationStage::NONE ? CultivationStage::PLANTING : $stage + 1;
                $context[] = ContextualMessage::make(
                    'farmer-lot-' . (int) $lot['id_lote'] . '-next-' . $next,
                    $stage === CultivationStage::NONE ? 'warning' : 'info',
                    'Próxima etapa: ' . CultivationStage::label($next),
                    'El lote ' . (string) ($lot['ubicacion'] ?? ('#' . $lot['id_lote'])) . ' está listo para continuar su secuencia agrícola.',
                    'Ver lote en este panel',
                    '#lote',
                    $next === CultivationStage::IRRIGATION ? 'water_drop' : 'agriculture'
                );
                $shownLots++;
                if ($shownLots >= 3) {
                    break;
                }
            }
            $context[] = ContextualMessage::make(
                'farmer-check-supplies',
                'info',
                'Revise los insumos antes de continuar',
                'Confirme las existencias y solicite lo necesario para la etapa activa de sus lotes.',
                'Ir a Insumos',
                '#insumos',
                'science'
            );
        }

        return $data + [
            'contextual_messages' => $context,
            'total_lotes' => count($data['lotes']),
            'etapas' => $stages,
            'farmer_today' => $days[(int) date('w')] . ', ' . date('j') . ' de ' . $months[(int) date('n') - 1] . ' de ' . date('Y'),
        ];
    }
}
