<?php
declare(strict_types=1);
namespace App\Modules\Solicitudes\Controllers;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Url;
use App\Modules\Solicitudes\Services\SolicitudService;
use App\Shared\Exceptions\ValidationException;
final class SolicitudController extends Controller
{
    public function __construct(private readonly SolicitudService $service,private readonly Auth $auth,private readonly Csrf $csrf,private readonly Session $session){}
    public function review(Request $request): Response
    {
        return $this->run($request,function(int $id,string $action):void{$this->service->review($id,$action);},'/dashboard/admin#solicitudes');
    }
    public function adminPage(Request $request): Response
    {
        return $this->render(__DIR__.'/../Views/admin.php',$this->service->adminDashboard());
    }
    public function history(Request $request): Response
    {
        $user=$this->auth->user();
        return $this->render(__DIR__.'/../Views/history.php',$this->service->userHistory($user['id_usuario']));
    }
    public function process(Request $request): Response
    {
        $user=$this->auth->user();
        return $this->run($request,function(int $id,string $action)use($user):void{$this->service->process($id,$action,$user['id_usuario']);},'/dashboard/bodega');
    }
    public function create(Request $request): Response
    {
        $user=$this->auth->user();try{$this->csrf->validate((string)$request->input('_token',''));$count=$this->service->createManual($user['id_usuario'],$request->all());$this->session->flash('success',"Solicitud enviada con {$count} insumo(s).");}catch(ValidationException $e){$messages=array_merge(...array_values($e->errors()));$this->session->flash('error',implode(' ',$messages));}return $this->redirect(Url::route('/dashboard/agricultor',['tab'=>'insumos']));
    }
    private function run(Request $request,callable $operation,string $return): Response
    {
        try{$this->csrf->validate((string)$request->input('_token',''));$id=(int)$request->input('id_producto_solicitud',$request->input('id_solicitud',0));$action=(string)$request->input('accion',$request->input('action',''));$action=str_replace(['aprobar_solicitud','rechazar_solicitud'],['aprobar','rechazar'],$action);$operation($id,$action);$message='Solicitud procesada correctamente.';$this->session->flash('success',$message);if($request->expectsJson()){return $this->json(['success'=>true,'message'=>$message]);}}catch(ValidationException $e){$messages=array_merge(...array_values($e->errors()));$message=implode(' ',$messages);$this->session->flash('error',$message);if($request->expectsJson()){return $this->json(['success'=>false,'message'=>$message],422);}}
        [$path,$fragment]=array_pad(explode('#',$return,2),2,'');
        return $this->redirect(Url::route($path).($fragment!==''?'#'.$fragment:''));
    }
}
