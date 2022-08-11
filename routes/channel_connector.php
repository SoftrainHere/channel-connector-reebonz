<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Mxncommerce\ChannelConnector\Http\Controllers\Api\SaveDataFromRemoteController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

Route::middleware(['auth:api'])->group(function () {
    Route::post('remote', [SaveDataFromRemoteController::class, 'save'])
        ->withoutMiddleware(['auth:api']);
});

Route::fallback(function () {
    throw new NotFoundHttpException('route not found.', null, 404);
});
