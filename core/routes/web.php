<?php

use PHPMailer\PHPMailer\PHPMailer;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DeployController;
use App\Http\Controllers\ReviewController;
use PHPMailer\PHPMailer\Exception;

Broadcast::routes([
    'prefix' => 'epay/',
    'middleware' => ['web', 'auth'],
]);

Route::get('/clear', function () {
    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
});

Route::get('cron', 'CronController@cron')->name('cron');

Route::controller('CronController')->prefix('cron')->name('cron.')->group(function () {
    Route::get('fiat-rate', 'fiatRate')->name('fiat.rate');
    Route::get('all', 'all')->name('all');
});

// User Support Ticket
Route::controller('TicketController')->prefix('ticket')->name('ticket.')->group(function () {
    Route::get('/', 'supportTicket')->name('index');
    Route::get('new', 'openSupportTicket')->name('open');
    Route::post('create', 'storeSupportTicket')->name('store');
    Route::get('view/{ticket}', 'viewTicket')->name('view');
    Route::post('reply/{id}', 'replyTicket')->name('reply');
    Route::post('close/{id}', 'closeTicket')->name('close');
    Route::get('download/{attachment_id}', 'ticketDownload')->name('download');
});

Route::controller('User\ExchangeController')->prefix('exchange')->name('exchange.')->group(function () {
    Route::post('/', 'exchange')->name('start');
    Route::post('get/rate', 'getExchangeRate')->name('get.alert');
    Route::get('best/rates', 'bestRates')->name('best.rates');
});

Route::get('app/deposit/confirm/{hash}', 'Gateway\PaymentController@appDepositConfirm')->name('deposit.app.confirm');

Route::controller('SiteController')->group(function () {
    Route::get('/contact', 'contact')->name('contact');
    Route::post('/contact', 'contactSubmit');
    Route::get('/change/{lang?}', 'changeLanguage')->name('lang');
    Route::get('cookie-policy', 'cookiePolicy')->name('cookie.policy');
    Route::get('/cookie/accept', 'cookieAccept')->name('cookie.accept');
    Route::get('blog', 'blog')->name('blog');
    Route::get('blog/{slug}', 'blogDetails')->name('blog.details');
    Route::get('faq', 'faq')->name('faq');
    Route::get('policy/{slug}', 'policyPages')->name('policy.pages');
    Route::post('subscribe', 'subscribe')->name('subscribe');
    Route::get('exchange/tracking', 'trackExchange')->name('exchange.tracking');
    Route::get('download/exchange/pdf/{hash}/{id}', 'downloadPdf')->name('download.exchange.pdf');

    Route::get('placeholder-image/{size}', 'placeholderImage')->withoutMiddleware('maintenance')->name('placeholder.image');
    Route::get('maintenance-mode', 'maintenance')->withoutMiddleware('maintenance')->name('maintenance');
    Route::get('/{slug}', 'pages')->name('pages');
    Route::get('/', 'index')->name('home');

    Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index');
    Route::post('/reviews', [ReviewController::class, 'store'])->name('reviews.store');
    Route::get('/reviews/all', [ReviewController::class, 'allReviews'])->name('reviews.list');
    Route::get('/review/auth-user/', [ReviewController::class, 'getAuthUserReview'])->name('getAuthUserReview');
});

// Route::get('test-email', function(){

//     $mail = new PHPMailer(true);

//     try {
//         $mail->isSMTP();
//         $mail->Host = 'mail.yourdomain.com';  // use shared host SMTP
//         $mail->SMTPAuth = true;
//         $mail->Username = 'username@yourdomain.com';
//         $mail->Password = 'yourpassword';
//         $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // or TLS
//         $mail->Port = 465; // or 587

//         $mail->setFrom('username@yourdomain.com', 'Test');
//         $mail->addAddress('youremail@example.com', 'You');

//         $mail->isHTML(true);
//         $mail->Subject = 'Test Email';
//         $mail->Body    = 'This is a test email from shared hosting.';

//         $mail->SMTPDebug = 2;   // shows debug info
//         $mail->Debugoutput = 'html';

//         $mail->send();
//         echo 'Email sent successfully!';
//     } catch (Exception $e) {
//         echo "Mailer Error: {$mail->ErrorInfo}";
//     }
// });

// routes/web.php
Route::get('/deploy/{token}', [DeployController::class, 'deploy']);
