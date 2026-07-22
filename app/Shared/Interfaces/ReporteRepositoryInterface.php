<?php

declare(strict_types=1);

namespace App\Shared\Interfaces;

interface ReporteRepositoryInterface
{
    /** @return array<string,int> */ public function totals():array;
    /** @return list<array<string,mixed>> */ public function usersByRole():array;
    /** @return list<array<string,mixed>> */ public function users():array;
    /** @return list<array<string,mixed>> */ public function cropsByType():array;
    /** @return list<array<string,mixed>> */ public function recentCrops():array;
    /** @return list<array<string,mixed>> */ public function recentActivity():array;
    /** @return list<array<string,mixed>> */ public function processedRequests():array;
    /** @return list<array<string,mixed>> */ public function invoiceProducts():array;
}
