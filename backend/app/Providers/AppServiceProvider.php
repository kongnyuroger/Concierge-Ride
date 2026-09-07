use App\Models\Job;
use App\Observers\JobObserver;

public function boot(): void
{
    Job::observe(JobObserver::class);
}