<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\Status;

class Contact extends Model
{
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'nick_name',
        'web_url',
        'address',
        'birthday',
        'notes',
        'country',
        'zip_code',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'contact';

    public function scopeActive($query)
    {
        return $query->where('status', Status::ACTIVE);
    }

    public function scopeApplySearchFilters($query, $search)
    {
        if ($search) {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('last_name', 'like', "%$search%")
                    ->orWhere('birthday', 'like', "%$search%")
                    ->orWhereHas('emails', fn($q) => $q->where('is_primary', 1)->where('email', 'like', "%$search%"))
                    ->orWhereHas('phones', fn($q) => $q->where('is_primary', 1)->where('phone', 'like', "%$search%"));
            });
        }
    }

    public function scopeApplySort($query, $sortKey, $sortOrder)
    {
        if ($sortKey === 'email') {
            // Sort by email
            $query->orderByEmail($sortOrder);
        } elseif ($sortKey === 'phone') {
            // Sort by phone
            $query->orderByPhone($sortOrder);
        } else {
            // Default sorting by main contact column
            $query->orderBy($sortKey, $sortOrder);
        }
    }

    public function scopeOrderByEmail($query, $sortOrder)
    {
        $query->orderBy(
            ContactEmail::select('email')
                ->whereColumn('contact_email.contact_id', 'contact.id')
                ->where('contact_email.is_primary', 1)
                ->orderBy('contact_email.email', $sortOrder)
                ->limit(1),
            $sortOrder
        );
    }

    public function scopeOrderByPhone($query, $sortOrder)
    {
        $query->orderBy(
            ContactPhone::select('phone')
                ->whereColumn('contact_phone.contact_id', 'contact.id')
                ->where('contact_phone.is_primary', 1)
                ->orderBy('contact_phone.phone', $sortOrder)
                ->limit(1),
            $sortOrder
        );
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function emails()
    {
        return $this->hasMany(ContactEmail::class);
    }

    public function phones()
    {
        return $this->hasMany(ContactPhone::class);
    }
}
