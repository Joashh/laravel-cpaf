<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewAppointment extends Model
{
    use HasFactory;

    protected $table = 'new_appointments'; // Ensures correct table mapping

    protected $fillable = [
        'type_of_appointments',
        'position',
        'appointment',
        'appointment_effectivity_date',
        'photo_url',
    ];
}
