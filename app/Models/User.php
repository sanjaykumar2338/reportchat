<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'password',
        'fcm_token',
        'company',
        'role',
        'permissions',
        'report_categories'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function companyRelation()
    {
        return $this->belongsTo(Company::class, 'company');
    }

    protected $casts = [
        'permissions' => 'array',
        'report_categories' => 'array',
    ];

    public function allowedReportCategories(): array {
        return $this->report_categories ?? [];
    }

    public function canSeeReportCategory(string $key): bool {
        $allowed = $this->allowedReportCategories();
        // superadmin sees all
        if ($this->isSuperAdmin()) return true;
        return in_array($key, $allowed, true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function hasPermission(string $module): bool
    {
        if ($this->isSuperAdmin()) return true;
        $p = $this->permissions ?? [];
        if (!array_key_exists($module, $p)) return false;

        $v = $p[$module];
        return ($v === true || $v === 1 || $v === '1' || $v === 'true');
    }
}
