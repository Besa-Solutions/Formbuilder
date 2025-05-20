<?php

namespace App\Filters\V1;

use Illuminate\Http\Request;

class ApiFilter {
    protected $allowedParms = [
        
    ];

    protected $columnMap =[
        
    ];

    protected $operatorMap =[
       
    ];
 

    public function transform(REquest $request) {
        $eloQuery = [];

        foreach ($this->allowedParms as $parm => $operators) {
            $query = $request->query($parm);
            if (!isset($query)) {
                continue;
            }

            $column = $this->columnMap[$parm] ?? $parm;

            foreach ($operators as $operator) {
                if (isset($query[$operator])) {
                    $eloQuery[] = [$column, $this->operatorMap[$operator], $query[$operator]];
                }
            }
        }
        return $eloQuery;
    }
}