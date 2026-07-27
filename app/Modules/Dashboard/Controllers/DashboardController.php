<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Controllers;

use App\Core\Auth;use App\Core\Controller;use App\Core\Request;use App\Core\Response;use App\Modules\Dashboard\Services\DashboardService;
final class DashboardController extends Controller{public function __construct(private readonly DashboardService $service,private readonly Auth $auth){}public function admin(Request $request):Response{return $this->render(__DIR__.'/../Views/admin.php',$this->service->admin());}public function warehouse(Request $request):Response{return $this->render(__DIR__.'/../Views/warehouse.php',$this->service->warehouse());}public function farmer(Request $request):Response{$user=$this->auth->user();return $this->render(__DIR__.'/../Views/farmer.php',$this->service->farmer((string)$user['id_usuario']));}}
