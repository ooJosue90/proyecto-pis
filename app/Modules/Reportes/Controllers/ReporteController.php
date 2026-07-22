<?php

declare(strict_types=1);

namespace App\Modules\Reportes\Controllers;

use App\Core\Controller;use App\Core\Request;use App\Core\Response;use App\Modules\Reportes\Services\ReporteService;

final class ReporteController extends Controller
{
    public function __construct(private readonly ReporteService $service){}
    public function admin(Request $request):Response{return $this->render(__DIR__.'/../Views/admin.php',$this->service->adminDashboard());}
    public function requests(Request $request):Response{return $this->render(__DIR__.'/../Views/requests.php',$this->service->processedRequests());}
    public function invoiceProducts(Request $request):Response{return $this->render(__DIR__.'/../Views/invoice-products.php',$this->service->invoiceProducts());}
}
