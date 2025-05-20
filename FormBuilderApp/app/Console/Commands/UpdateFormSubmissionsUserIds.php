<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FormSubmission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateFormSubmissionsUserIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-form-submissions-user-ids';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update form submissions with user IDs based on IP addresses';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating form submissions with user IDs...');

        // Get all submissions without a user_id
        $submissions = FormSubmission::whereNull('user_id')->get();
        $this->info('Found ' . $submissions->count() . ' submissions without user_id');

        $updated = 0;
        $skipped = 0;

        // Group submissions by IP address
        $submissionsByIp = $submissions->groupBy('submission_ip');

        foreach ($submissionsByIp as $ip => $ipSubmissions) {
            // Find the latest authenticated session with this IP
            $user = DB::table('sessions')
                ->where('ip_address', $ip)
                ->whereNotNull('user_id')
                ->orderBy('last_activity', 'desc')
                ->first();

            if ($user) {
                // Update all submissions from this IP to link to this user
                FormSubmission::where('submission_ip', $ip)
                    ->whereNull('user_id')
                    ->update(['user_id' => $user->user_id]);
                
                $updated += $ipSubmissions->count();
                $this->info("Updated {$ipSubmissions->count()} submissions for IP: {$ip} with user ID: {$user->user_id}");
            } else {
                $skipped += $ipSubmissions->count();
                $this->warn("Could not find user for IP: {$ip}, skipped {$ipSubmissions->count()} submissions");
            }
        }

        // For any remaining submissions without a user_id but marked not anonymous,
        // try to match to a non-admin user if there's only one
        if (User::where('role', 'guest')->count() === 1) {
            $guestUser = User::where('role', 'guest')->first();
            
            $remainingNonAnonymous = FormSubmission::whereNull('user_id')
                ->where('is_anonymous', false)
                ->count();
                
            if ($remainingNonAnonymous > 0) {
                FormSubmission::whereNull('user_id')
                    ->where('is_anonymous', false)
                    ->update(['user_id' => $guestUser->id]);
                    
                $updated += $remainingNonAnonymous;
                $this->info("Updated {$remainingNonAnonymous} non-anonymous submissions with default guest user ID: {$guestUser->id}");
            }
        }

        $this->info("Completed! Updated {$updated} submissions, skipped {$skipped} submissions.");
    }
}
