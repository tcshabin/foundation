<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactEmail extends Model
{
    protected $fillable = [
        'contact_id',
        'email',
        'tag',
        'is_primary',
    ];

    public $timestamps = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'contact_email';

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
