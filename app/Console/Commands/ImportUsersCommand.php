<?php

namespace App\Console\Commands;

use App\Actions\CsvDecodeAction;
use App\Models\Account;
use App\Models\User;
use Illuminate\Console\Command;

class ImportUsersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(CsvDecodeAction $action)
    {
        $server = env('SSG_SERVER');
        $ftpUser = env('SSG_USER');
        $pass = env('SSG_PASS');

        $this->info('Importing 4S users');

        $src = "ftp://$ftpUser:$pass@$server/Unimog/4s_users.csv";
        $users = $action->handle($src, ',');

        // Get all accounts indexed by code (not filtered by region)
        $accounts = Account::where('region_id', 2)->pluck('id', 'code');

        $imported = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($users as $userData) {
            try {
                // Check if user already exists
                if (User::where('email', $userData['email'])->exists()) {
                    $this->warn("User already exists: {$userData['email']}");
                    $skipped++;
                    continue;
                }

                // Check if account code exists
                if (!isset($accounts[$userData['account_code']])) {
                    $this->error("Account not found: {$userData['account_code']} for {$userData['email']}");
                    $errors++;
                    continue;
                }

                // Create user with random password (they'll need to reset)
                $user = User::create([
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(16)),
                    'account_id' => $accounts[$userData['account_code']],
                ]);

                // Assign Customer role
                $user->assignRole('Customer');

                $this->info("Imported: {$userData['email']} -> Account: {$userData['account_code']}");
                $imported++;
            } catch (\Exception $e) {
                $this->error("Error importing {$userData['email']}: {$e->getMessage()}");
                $errors++;
            }
        }

        $this->info("\n=== Import Summary ===");
        $this->info("Imported: $imported");
        $this->warn("Skipped: $skipped");
        $this->error("Errors: $errors");
        $this->info("Total: " . count($users));
    }
}
