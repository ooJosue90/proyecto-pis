<?php
declare(strict_types=1);
namespace App\Modules\Inventario\Controllers;
use App\Core\Auth;use App\Core\Controller;use App\Core\Csrf;use App\Core\Request;use App\Core\Response;use App\Core\Session;use App\Core\Url;use App\Modules\Inventario\Services\InventarioService;use App\Shared\Exceptions\ValidationException;
final class InventarioController extends Controller
{
    public function __construct(private readonly InventarioService $service,private readonly Auth $auth,private readonly Csrf $csrf,private readonly Session $session){}
    public function index(Request $request):Response{$user=$this->auth->user();return $this->render(__DIR__.'/../Views/index.php',['items'=>$this->service->all(),'user'=>$user,'csrfToken'=>$this->csrf->token(),'success'=>$this->session->flash('success'),'error'=>$this->session->flash('error')]);}
    public function store(Request $request):Response{return $this->mutate($request,fn(array $user)=>$this->service->create($user['id_usuario'],$request->all()),'Insumo registrado correctamente.');}
    public function adjust(Request $request):Response{return $this->mutate($request,fn(array $user)=>$this->service->adjust($user['id_usuario'],$request->all()),'Stock actualizado correctamente.');}
    private function mutate(Request $request,callable $operation,string $message):Response{try{$this->csrf->validate((string)$request->input('_token',''));$operation($this->auth->user());$this->session->flash('success',$message);}catch(ValidationException $e){$messages=array_merge(...array_values($e->errors()));$this->session->flash('error',implode(' ',$messages));}return $this->redirect(Url::route('/inventario'));}
}
