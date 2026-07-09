<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductionJobFile extends Model
{
    protected $table = 'production_job_files';

    protected $fillable = [
        'job_id', 'original_name', 'file_path', 'mime_type', 'file_size', 'label', 'uploaded_by',
    ];

    public function job()
    {
        return $this->belongsTo(ProductionJob::class, 'job_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getUrlAttribute(): string
    {
        return route('production.file.download', $this->id);
    }

    public function getIconAttribute(): string
    {
        $mime = $this->mime_type ?? '';
        if (str_starts_with($mime, 'image/')) return '🖼️';
        if ($mime === 'application/pdf') return '📄';
        return '📎';
    }

    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}
