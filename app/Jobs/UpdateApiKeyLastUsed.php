<?php

namespace App\Jobs;

use App\Models\ApiKey;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateApiKeyLastUsed implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $apiKeyId) {}

    public function handle(): void
    {
        // we use a direct DB query here instead of loading the model to save milliseconds
        ApiKey::where('id', $this->apiKeyId)->update([
            'last_used_at' => now(),
        ]);
    }
}
