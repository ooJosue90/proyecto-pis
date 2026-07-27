<?php

declare(strict_types=1);

namespace App\Modules\Movimientos\Controllers;

use App\Core\Controller;use App\Core\Request;use App\Core\Response;use App\Modules\Movimientos\Repositories\MovimientoRepository;
final class MovimientoController extends Controller{public function __construct(private readonly MovimientoRepository $repository){}public function index(Request $request):Response{return $this->render(__DIR__.'/../Views/index.php',$this->repository->dashboard());}}
