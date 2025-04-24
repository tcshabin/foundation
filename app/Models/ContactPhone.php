<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactPhone extends Model
{
    protected $fillable = [
        'contact_id',
        'phone',
        'tag',
        'is_primary',
    ];

    public $timestamps = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'contact_phone';

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
