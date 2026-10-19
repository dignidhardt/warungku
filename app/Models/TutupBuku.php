<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TutupBuku extends Model
{
    protected $table = 'tutup_bukus';

    protected $fillable = [
        'tanggal',
        'kas_seharusnya',
        'kas_fisik',
        'selisih',
        'catatan',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'kas_seharusnya' => 'integer',
            'kas_fisik' => 'integer',
            'selisih' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getStatusSelisihAttribute(): string
    {
        return match (true) {
            $this->selisih === 0 => 'pas',
            $this->selisih > 0 => 'lebih',
            default => 'kurang',
        };
    }
}
