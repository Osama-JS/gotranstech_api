<?php

namespace App\Console\Commands;

use App\Models\Task;
use Illuminate\Console\Command;

class ExpireTasksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire available tasks whose countdown timer has ended';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = Task::where('status', 'available')
            ->where('expires_at', '<=', now())
            ->update(['status' => 'expired']);

        $this->info("Expired {$count} tasks successfully.");

        return Command::SUCCESS;
    }
}
