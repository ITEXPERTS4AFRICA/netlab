<?php

namespace App\Console\Commands;

use App\Models\Lab;
use App\Services\CiscoApiService;
use Illuminate\Console\Command;

class SyncCmlLabs extends Command
{
    protected $signature = 'cml:sync-labs {--force}';
    protected $description = 'Sync labs from CML into local database';

    public function handle(CiscoApiService $cisco)
    {
        $token = session('cml_token');
        if (! $token && ! $this->option('force')) {
            $this->error('No CML token in session. Run with --force to attempt unauthenticated sync.');
            return 1;
        }

        $this->info('Fetching labs from CML...');
        $res = $cisco->getLabs($token);
        if (! is_array($res) || isset($res['error'])) {
            $this->error('Failed to fetch labs: ' . json_encode($res));
            return 2;
        }

        $count = 0;
        foreach ($res as $item) {
            // If API returns only IDs, fetch details
            if (is_string($item)) {
                $detail = $cisco->getLab($token, $item);
                if (! is_array($detail) || isset($detail['error'])) {
                    continue;
                }
                $item = $detail;
            }

            $lab = Lab::updateOrCreate(
                ['cml_id' => $item['id'] ?? $item['uuid'] ?? null],
                [
                    'name' => $item['name'] ?? null,
                    'description' => $item['description'] ?? null,
                    'metadata' => $item,
                    'status' => $item['status'] ?? null,
                ]
            );

            $count++;
        }

        $this->info("Synced {$count} labs.");
        return 0;
    }
}


