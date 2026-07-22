<?php

declare(strict_types=1);

namespace App\Modules\Asistente\Services;

use App\Shared\Interfaces\AssistantDataRepositoryInterface;

final class ContextBuilder
{
    public function __construct(private readonly AssistantDataRepositoryInterface $repository,private readonly int $maxRows=20,private readonly int $maxChars=12000){}
    public function build(string $role,string $userId,array $analysis):string
    {
        if($analysis['category']==='general'){return 'Consulta de conocimiento agrícola general. No se proporcionan datos internos.';}
        if($analysis['category']==='action'){return 'ADA no ejecuta cambios. Debe orientar al usuario hacia el módulo autorizado correspondiente.';}
        $topics=$analysis['topics'];if(in_array('reportes',$topics,true)){$topics=array_values(array_unique(array_merge($topics,match($role){'Agricultor'=>['cultivos','lotes','solicitudes'],'Bodeguero'=>['inventario','pedidos','solicitudes'],default=>[]})));}
        $parts=[];foreach($topics as $topic){$rows=$this->redact($this->repository->context($topic,$role,$userId,$this->maxRows));$parts[]=strtoupper($topic).":\n".json_encode($rows,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);if(strlen(implode("\n\n",$parts))>=$this->maxChars){break;}}
        $context=implode("\n\n",$parts);return mb_substr($context===''?'No hay contexto interno disponible.':$context,0,$this->maxChars);
    }
    private function redact(array $rows):array{foreach($rows as &$row){foreach(array_keys($row) as $key){if(preg_match('/password|contrasena|token|secret|api.?key/i',(string)$key)){unset($row[$key]);}}}unset($row);return $rows;}
}
