use App\Http\Controllers\Api\StripeWebhookController;

Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle']);