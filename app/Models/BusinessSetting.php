<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessSetting extends Model
{
    use HasFactory;
    // protected $casts = [
    //     'value' => 'json',
    // ];
    // protected $guarded = [];
    protected $table = 'business_settings';
    protected $fillable = ['key', 'value']; 
}