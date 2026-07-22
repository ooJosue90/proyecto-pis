<?php

declare(strict_types=1);

namespace App\Modules\Asistente\Controllers;

use App\Core\Auth;use App\Core\Controller;use App\Core\Csrf;use App\Core\Request;use App\Core\Response;use App\Core\Session;use App\Core\Url;use App\Modules\Asistente\Services\ContextBuilder;use App\Modules\Asistente\Services\GeminiService;use App\Modules\Asistente\Services\PermissionFilter;use App\Shared\Exceptions\ValidationException;

final class ChatController extends Controller
{
    public function __construct(private readonly PermissionFilter $permissions,private readonly ContextBuilder $context,private readonly GeminiService $gemini,private readonly Auth $auth,private readonly Csrf $csrf,private readonly Session $session){}
    public function chat(Request $request):Response
    {
        try{$this->csrf->validate((string)$request->input('_token',''));$question=trim((string)$request->input('pregunta',''));if($question===''||mb_strlen($question)>1000){throw new ValidationException(['pregunta'=>['Escriba una pregunta válida de hasta 1000 caracteres.']]);}
            $user=$this->auth->user();$role=$user['rol'];$analysis=$this->permissions->analyze($question);if(!$this->permissions->authorized($role,$analysis)){return $this->json(['success'=>false,'ok'=>false,'rol'=>$role,'respuesta'=>'Esa información pertenece a un módulo no disponible para su perfil. Solicítela al administrador.','enlaces'=>[],'navegar_a'=>null],403);}
            $links=$this->links($role,$analysis['topics']);if($this->navigation($question)&&$links!==[]){return $this->json(['success'=>true,'ok'=>true,'rol'=>$role,'respuesta'=>'Le llevo al módulo solicitado.','enlaces'=>[$links[0]],'navegar_a'=>$links[0]]);}
            $context=$this->context->build($role,$user['id_usuario'],$analysis);$answer=$this->gemini->answer($question,$context,$role,(string)($user['nombre']??'Usuario'));$this->session->put('ada_ultima_pregunta',mb_strtolower($question,'UTF-8'));$this->session->put('ada_ultima_interaccion_fecha',date('Y-m-d'));
            return $this->json(['success'=>true,'ok'=>true,'rol'=>$role,'respuesta'=>$answer,'enlaces'=>$links,'navegar_a'=>null]);
        }catch(ValidationException $e){return $this->json(['success'=>false,'ok'=>false,'rol'=>$this->auth->role()??'Invitado','respuesta'=>implode(' ',array_merge(...array_values($e->errors()))),'enlaces'=>[],'navegar_a'=>null],422);}
    }
    private function navigation(string $question):bool{$text=mb_strtolower($question,'UTF-8');return str_contains($text,'llévame')||str_contains($text,'llevame')||str_contains($text,'abre el módulo')||str_contains($text,'ir a ');}
    private function links(string $role,array $topics):array{$admin=Url::route('/dashboard/admin');$farmer=Url::route('/dashboard/agricultor');$warehouse=Url::route('/dashboard/bodega');$base=['Administrador'=>['usuarios'=>['Ir a Usuarios',$admin.'#usuarios'],'reportes'=>['Ir a Reportes',$admin.'#reportes'],'facturas'=>['Ir a Facturas',$admin.'#facturas'],'cultivos'=>['Ir a Cultivos',$admin.'#cultivos'],'lotes'=>['Ir a Lotes',$admin.'#cultivos'],'inventario'=>['Ir a Inventario',$admin.'#pedidos-proveedores'],'solicitudes'=>['Ir a Solicitudes',$admin.'#solicitudes']],'Agricultor'=>['cultivos'=>['Ir a Cultivos',$farmer.'?tab=cultivo'],'lotes'=>['Ir a Lotes',$farmer.'?tab=lote'],'solicitudes'=>['Solicitar insumos',$farmer.'?tab=insumos'],'plagas'=>['Ir a Fitosanitario',Url::route('/plagas')]],'Bodeguero'=>['inventario'=>['Ir al Inventario',$warehouse],'facturas'=>['Ir a Facturas',Url::route('/facturas/recepcion')],'solicitudes'=>['Ir a Solicitudes',$warehouse]]];$links=[];foreach($topics as $topic){if(isset($base[$role][$topic])){[$label,$href]=$base[$role][$topic];$links[]=['label'=>$label,'href'=>$href,'icon'=>'fas fa-arrow-right'];if(count($links)===3){break;}}}return $links;}
}
