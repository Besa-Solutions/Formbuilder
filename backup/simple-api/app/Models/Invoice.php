<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use App\Http\Resources\V1\InvoiceCollection;

class Invoice extends Model
{
    /** @use HasFactory<\Database\Factories\InvoiceFactory> */
    use HasFactory;
    public function customer() {
        return $this->belongsTo(Customer::class);
    }

    public function index(Request $request)
{
    $filter = new \App\Filters\V1\InvoicesFilter();
    $queryItems = $filter->transform($request);

    if (count($queryItems) === 0) {
        return new InvoiceCollection(Invoice::paginate());
    } else {
        return new InvoiceCollection(Invoice::where($queryItems)->paginate());
    }
}
}
