<?php

namespace App\Filters\V1;

use Illuminate\Http\Request;

class InvoicesFilter {
    protected $allowedParams = [
        'customer_id' => ['eq'],
        'amount'      => ['eq', 'gt', 'lt'],
        'status'      => ['eq', 'ne'],
        'due_date'    => ['eq', 'gt', 'lt'],
    ];

    protected $operatorMap = [
        'eq' => '=',
        'gt' => '>',
        'lt' => '<',
        'ne' => '<>', 
    ];

    public function transform(Request $request)
    {
        $eloQuery = [];

        foreach ($this->allowedParams as $param => $operators) {
            $queryParam = $request->query($param);
            if (!isset($queryParam)) {
                continue;
            }

            foreach ($operators as $operator) {
                if (isset($queryParam[$operator])) {
                    $eloQuery[] = [$param, $this->operatorMap[$operator], $queryParam[$operator]];
                }
            }
        }

        return $eloQuery;
    }
}