<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $table = 'tbl_customers';
    protected $guarded = [];

    public function orders()
    {
        return $this->hasMany('App\Order', 'customer_id');
    }
}
