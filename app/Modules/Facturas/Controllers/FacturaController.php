<?php

declare(strict_types=1);

namespace App\Modules\Facturas\Controllers;

use App\Core\Auth;use App\Core\Controller;use App\Core\Csrf;use App\Core\Request;use App\Core\Response;use App\Core\Session;use App\Core\Url;use App\Modules\Facturas\Services\FacturaService;use App\Shared\Exceptions\ValidationException;use App\Shared\Support\ActionGuidance;

final class FacturaController extends Controller
{
    public function __construct(private readonly FacturaService $service,private readonly Auth $auth,private readonly Csrf $csrf,private readonly Session $session){}
    public function reception(Request $request):Response{$user=$this->auth->user();return $this->render(__DIR__.'/../Views/reception.php',array_merge($this->service->receptionDashboard($user['id_usuario'],(int)$request->input('pedido_id',0)),['csrfToken'=>$this->csrf->token()]));}
    public function receive(Request $request):Response{try{$this->csrf->validate((string)$request->input('_token',''));$user=$this->auth->user();$result=$this->service->receive($user['id_usuario'],$request->all());$this->session->flash('mensaje',"Factura {$result['number']} registrada. El inventario fue actualizado; ahora puede comprobar el siguiente pedido pendiente.");$this->session->flash('next_step',ActionGuidance::encode('Inventario actualizado','Verifique las nuevas existencias y después continúe con el siguiente pedido pendiente.','Ver inventario',Url::route('/inventario'),'success','fa-boxes-stacked'));}catch(ValidationException $e){$this->session->flash('error',implode(' ',array_merge(...array_values($e->errors()))));}return $this->redirect(Url::route('/facturas/recepcion'));}
    public function index(Request $request):Response{return $this->render(__DIR__.'/../Views/index.php',array_merge($this->service->adminDashboard($request->all()),['csrfToken'=>$this->csrf->token()]));}
    public function review(Request $request):Response{try{$this->csrf->validate((string)$request->input('_token',''));$user=$this->auth->user();$message=$this->service->review((int)$request->input('id_factura_compra',0),(string)$request->input('action',''),$user['id_usuario']);return $this->json(['success'=>true,'message'=>$message]);}catch(ValidationException $e){return $this->json(['success'=>false,'message'=>implode(' ',array_merge(...array_values($e->errors())))],422);}}
    public function detail(Request $request):Response{try{return $this->render(__DIR__.'/../Views/detail.php',$this->service->detail((int)$request->route('id',0)));}catch(ValidationException $e){return new Response('<div class="alert alert-danger">Factura no encontrada.</div>',404,['Content-Type'=>'text/html; charset=utf-8']);}}
}
