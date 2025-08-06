<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApiRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'method',
        'endpoint',
        'client_ip',
        'user_agent',
        'request_headers',
        'request_body',
        'query_params',
        'response_status',
        'response_body',
        'response_time_ms',
        'user_id',
    ];

    protected $casts = [
        'request_headers' => 'array',
        'request_body' => 'array',
        'query_params' => 'array',
        'response_body' => 'array',
        'response_time_ms' => 'integer',
    ];

    /**
     * Get the user that made the request.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to filter by method.
     */
    public function scopeByMethod($query, $method)
    {
        return $query->where('method', $method);
    }

    /**
     * Scope to filter by endpoint.
     */
    public function scopeByEndpoint($query, $endpoint)
    {
        return $query->where('endpoint', $endpoint);
    }

    /**
     * Scope to filter by client IP.
     */
    public function scopeByClientIp($query, $clientIp)
    {
        return $query->where('client_ip', $clientIp);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}
