<?php
declare(strict_types=1);
namespace App\Modules\Insumos\Controllers;
use App\Core\Auth;use App\Core\Controller;use App\Core\Request;use App\Core\Response;use App\Modules\Insumos\Services\InsumoCalculator;use App\Modules\Lotes\Services\LoteService;use App\Shared\Exceptions\ValidationException;
final class InsumoController extends Controller
{
    public function __construct(private readonly InsumoCalculator $calculator,private readonly LoteService $lotes,private readonly Auth $auth){}
    public function page(Request $request):Response
    {
        $user=$this->auth->user();
        $lotes=array_map(static fn($lote):array=>[
            'id_lote'=>$lote->id,
            'ubicacion'=>$lote->ubicacion,
            'tipo_cultivo'=>$lote->cultivo,
            'area'=>$lote->area,
        ],$this->lotes->listVisibleTo($user['id_usuario'],$user['rol']));
        return $this->render(__DIR__.'/../Views/calculator.php',[
            'lotes'=>$lotes,
            'totalArea'=>array_sum(array_column($lotes,'area')),
            'baseRecommendations'=>count($this->calculator->calculate(1)),
        ]);
    }
    public function calculate(Request $request):Response{$user=$this->auth->user();$id=filter_var($request->route('id'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);if($id===false)throw new ValidationException(['id'=>['Lote inválido.']]);$lote=$this->lotes->getVisible((int)$id,$user['id_usuario'],$user['rol']);return $this->json(['area'=>$lote->area,'insumos'=>$this->calculator->calculate($lote->area)]);}
}
