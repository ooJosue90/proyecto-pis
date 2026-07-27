<?php

declare(strict_types=1);

namespace App\Modules\Usuarios\Controllers;

use App\Core\Auth;use App\Core\Controller;use App\Core\Csrf;use App\Core\Request;use App\Core\Response;use App\Modules\Usuarios\Services\AdminUserService;use App\Shared\Exceptions\ValidationException;

final class AdminUserController extends Controller
{
    public function __construct(private readonly AdminUserService $service,private readonly Auth $auth,private readonly Csrf $csrf){}
    public function index(Request $request):Response{return $this->render(__DIR__.'/../Views/index.php',array_merge($this->service->dashboard(),['csrfToken'=>$this->csrf->token()]));}
    public function create(Request $request):Response{return $this->action($request,fn():string=>$this->service->create($request->all()),'Usuario creado exitosamente.');}
    public function update(Request $request):Response{return $this->action($request,function()use($request):void{$this->service->update($request->all());},'Usuario actualizado exitosamente.');}
    public function delete(Request $request):Response{$user=$this->auth->user();return $this->action($request,function()use($request,$user):void{$this->service->delete(trim((string)$request->input('id_usuario','')),$user['id_usuario']);},'Usuario eliminado exitosamente.');}
    private function action(Request $request,callable $operation,string $message):Response{try{$this->csrf->validate((string)$request->input('_token',''));$result=$operation();$payload=['success'=>true,'message'=>$message];if(is_string($result)){$payload['user_id']=$result;}return $this->json($payload);}catch(ValidationException $e){return $this->json(['success'=>false,'message'=>implode(' ',array_merge(...array_values($e->errors())))],422);}}
}
