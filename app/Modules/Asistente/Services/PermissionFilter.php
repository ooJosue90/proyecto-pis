<?php

declare(strict_types=1);

namespace App\Modules\Asistente\Services;

final class PermissionFilter
{
    private const TOPICS=['usuarios'=>['usuario','usuarios','empleado','roles'],'inventario'=>['inventario','stock','insumo','insumos','bodega'],'proveedores'=>['proveedor','proveedores'],'pedidos'=>['pedido','pedidos'],'facturas'=>['factura','facturas','financiero'],'movimientos'=>['movimiento','movimientos','entrada','salida'],'cultivos'=>['cultivo','cultivos'],'lotes'=>['lote','lotes','monitoreo','seguimiento'],'solicitudes'=>['solicitud','solicitudes','requerimiento'],'produccion'=>['produccion','cosecha','rendimiento','producido'],'plagas'=>['plaga','plagas'],'reportes'=>['reporte','reportes','resumen','dashboard','general'],'notificaciones'=>['notificacion','notificaciones','alerta','alertas']];
    private const ALLOWED=['Administrador'=>['usuarios','inventario','proveedores','pedidos','facturas','movimientos','cultivos','lotes','solicitudes','produccion','plagas','reportes','notificaciones'],'Agricultor'=>['cultivos','lotes','solicitudes','produccion','plagas','reportes'],'Bodeguero'=>['inventario','proveedores','pedidos','facturas','movimientos','solicitudes','reportes']];
    /** @return array{category:string,topics:list<string>} */
    public function analyze(string $question):array
    {
        $text=$this->normalize($question);$topics=[];foreach(self::TOPICS as $topic=>$words){foreach($words as $word){if(str_contains($text,$word)){$topics[]=$topic;break;}}}
        $internalIndicators=['registrado','registrada','sistema','lista','muestra','cuantos','cuantas','total','estado','pendiente','disponible','reporte','resumen','dashboard','monitoreo','actividad','reciente'];
        $administrative=['elimina','borrar','crear usuario','cambiar rol','restablecer contrasena','aprobar solicitud'];
        foreach($administrative as $phrase){if(str_contains($text,$phrase)){return ['category'=>'action','topics'=>$topics];}}
        foreach($internalIndicators as $word){if($topics!==[]&&str_contains($text,$word)){return ['category'=>'internal','topics'=>array_values(array_unique($topics))];}}
        return ['category'=>'general','topics'=>[]];
    }
    public function authorized(string $role,array $analysis):bool
    {
        if($analysis['category']==='general'){return true;}if($analysis['category']==='action'){return $role==='Administrador';}
        $allowed=self::ALLOWED[$role]??[];foreach($analysis['topics'] as $topic){if(!in_array($topic,$allowed,true)){return false;}}return $analysis['topics']!==[];
    }
    /** @return list<string> */ public function allowedTopics(string $role):array{return self::ALLOWED[$role]??[];}
    private function normalize(string $text):string{$text=mb_strtolower($text,'UTF-8');return str_replace(['á','é','í','ó','ú','ñ'],['a','e','i','o','u','n'],$text);}
}
