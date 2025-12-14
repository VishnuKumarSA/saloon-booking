<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'customer_name',
        'mobile',
        'booking_date',
        'start_time',
        'end_time',
        'beautician_id'
    ];
}
