<?php
declare(strict_types=1);
namespace App\Modules\Insumos\Services;
use App\Shared\Domain\CultivationStage;
final class InsumoCalculator
{
    private const ITEMS=[CultivationStage::PLANTING_LABEL=>[['Plántulas injertadas',167,'árboles'],['Compost orgánico',5000,'kg'],['Fosfato diamónico (DAP)',100,'kg'],['Cal agrícola',200,'kg'],['Estacas de tutorado',167,'unidades']],CultivationStage::IRRIGATION_LABEL=>[['Fertilizante NPK (20-20-20)',198,'kg'],['Quelatos de micronutrientes',10,'kg'],['Bioestimulantes (algas + aminoácidos)',5.25,'litros'],['Cinta de goteo o microaspersores',10000,'metros lineales'],['Medidor de pH y CE',1,'kit']],CultivationStage::HARVEST_LABEL=>[['Cajas plásticas ventiladas',300,'unidades'],['Solución desinfectante (NaClO)',50,'litros'],['Etiquetas y mallas',300,'unidades'],['Tijeras de poda',5,'unidades']]];
    public function calculate(float $area):array{if($area<=0)return[];$result=[];foreach(self::ITEMS as $stage=>$items){foreach($items as [$name,$perHectare,$unit]){$result[]=['etapa'=>$stage,'nombre'=>$name,'cantidad_total'=>$perHectare*$area,'unidad'=>$unit];}}return $result;}
}
