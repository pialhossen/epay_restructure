<?php

namespace App\Providers;

use Carbon\Carbon;
use App\Models\User;
use App\Lib\Searchable;
use App\Models\Currency;
use App\Models\Exchange;
use App\Models\Frontend;
use App\Constants\Status;
use App\Models\Withdrawal;
use App\Models\SupportTicket;
use App\Models\AdminNotification;
use App\Observers\CurrencyObserver;
use Illuminate\Support\Facades\URL;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Broadcast;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Builder::mixin(new Searchable);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // mocking date for testing purpose
        // Carbon::setTestNow(Carbon::create(2025, 9, 6));
        // dd(env('APP_ENV'));
        if(env('APP_ENV') !== 'local'){
            URL::forceScheme('https');
        }


        
        $appUrl = config('app.url');

        // Configure Currency observer so that during application boot it will be attached
        Currency::observe(CurrencyObserver::class);

        // if (
        //     app()->environment('local') &&
        //     (str_contains($appUrl, '127.0.0.1') || str_contains($appUrl, 'localhost'))
        // ) {
        //     URL::forceScheme('http');
        // }

        Schema::defaultStringLength(191);

        if (! cache()->get('SystemInstalled')) {
            $envFilePath = base_path('.env');
            if (! file_exists($envFilePath)) {
                header('Location: install');
                exit;
            }
            $envContents = file_get_contents($envFilePath);
            if (empty($envContents)) {
                header('Location: install');
                exit;
            } else {
                cache()->put('SystemInstalled', true);
            }
        }

        $viewShare['emptyMessage'] = 'Data not found';
        view()->share($viewShare);

        view()->composer('admin.partials.sidenav', function ($view) {
            $view->with([
                'bannedUsersCount' => User::banned()->count(),
                'emailUnverifiedUsersCount' => User::emailUnverified()->count(),
                'mobileUnverifiedUsersCount' => User::mobileUnverified()->count(),
                'kycUnverifiedUsersCount' => User::kycUnverified()->count(),
                'kycPendingUsersCount' => User::kycPending()->count(),
                'pendingTicketCount' => SupportTicket::whereIN('status', [Status::TICKET_OPEN, Status::TICKET_REPLY])->count(),
                'pendingWithdrawCount' => Withdrawal::pending()->count(),
                'updateAvailable' => version_compare(gs('available_version'), systemDetails()['version'], '>') ? 'v'.gs('available_version') : false,
                
                // 'pendingExchange' => Exchange::where('status', Status::EXCHANGE_PENDING)->count(),
                'pendingExchangeCount' => Exchange::where('status', Status::EXCHANGE_PENDING)->count(),
                'holdExchangeCount' => Exchange::where('status', Status::EXCHANGE_HOLD)->count(),
                'processingExchangeCount' => Exchange::where('status', Status::EXCHANGE_PROCESSING)->count(),
                'approvedExchangeCount' => Exchange::where('status', Status::EXCHANGE_APPROVED)->count(),
                'canceledExchangeCount' => Exchange::where('status', Status::EXCHANGE_CANCEL)->count(),
                'refundedExchangeCount' => Exchange::where('status', Status::EXCHANGE_REFUND)->count(),
                'allExchangeCount' => Exchange::where('status','!=', 0)->count(),
            ]);
        });

        view()->composer('admin.partials.topnav', function ($view) {
            $view->with([
                'adminNotifications' => AdminNotification::where('is_read', Status::NO)->with('user')->orderBy('id', 'desc')->take(10)->get(),
                'adminNotificationCount' => AdminNotification::where('is_read', Status::NO)->count(),
            ]);
        });

        view()->composer('partials.seo', function ($view) {
            $seo = Frontend::where('data_keys', 'seo.data')->first();
            $view->with([
                'seo' => $seo ? $seo->data_values : $seo,
            ]);
        });

        // if (gs('force_ssl')) {
        //     \URL::forceScheme('https');
        // }

        View::addNamespace('Template', resource_path('views/templates/'.activeTemplateName()));

        Paginator::useBootstrapFive();

        Broadcast::routes(['middleware' => ['auth:admin']]);
        
    }
}
