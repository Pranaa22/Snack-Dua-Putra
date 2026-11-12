<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReceiptController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

// Publik: satu domain, tanpa login
Route::get('/', [StoreController::class, 'index'])->name('store.home');
Route::get('/produk/{slug}', [StoreController::class, 'detail'])->name('store.product');
Route::get('/checkout', [StoreController::class, 'checkoutView'])->name('store.checkout');
Route::post('/checkout', [StoreController::class, 'placeOrder'])->middleware('throttle:10,1')->name('store.place-order');
Route::get('/pesanan/{code}', [StoreController::class, 'orderStatus'])->name('store.order');
Route::get('/api/katalog', [StoreController::class, 'catalog'])->name('store.catalog');
Route::post('/api/promo/validasi', [StoreController::class, 'validatePromo'])->middleware('throttle:30,1');
Route::get('/api/pesanan/{code}', [PaymentController::class, 'status'])->middleware('throttle:30,1');
Route::get('/api/pesanan/{code}/pay', [PaymentController::class, 'token'])->middleware('throttle:15,1');
Route::post('/midtrans/notification', [PaymentController::class, 'webhook'])
    ->withoutMiddleware([ValidateCsrfToken::class]);
Route::get('/struk/{token}', [ReceiptController::class, 'show'])->name('receipt.show');

// Login penjual: sesi Laravel, terpisah dari toko depan
Route::get('/admin/login', [AdminController::class, 'loginView'])->name('admin.login');
Route::post('/admin/login', [AdminController::class, 'login'])->middleware('throttle:5,1')->name('admin.login.post');
Route::middleware([EnsureAdmin::class])->prefix('admin')->name('admin.')->group(function () {
    Route::post('/logout', [AdminController::class, 'logout'])->name('logout');
    Route::get('/', fn () => redirect()->route('admin.dashboard'));
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/products', [AdminController::class, 'products'])->name('products');
    Route::get('/orders', [AdminController::class, 'orders'])->name('orders');
    Route::get('/kasir', [AdminController::class, 'pos'])->name('pos');
    Route::get('/laporan', [AdminController::class, 'reports'])->name('reports');
    Route::get('/riwayat', [AdminController::class, 'history'])->name('history');
    Route::get('/pengaturan', [AdminController::class, 'settings'])->name('settings');
    Route::get('/promo', [AdminController::class, 'promos'])->name('promos');

    Route::get('/api/data', [AdminController::class, 'data']);
    Route::post('/api/products', [AdminController::class, 'saveProduct']);
    Route::put('/api/products/{id}', [AdminController::class, 'saveProduct']);
    Route::delete('/api/products/{id}', [AdminController::class, 'deleteProduct']);
    Route::put('/api/settings', [AdminController::class, 'saveSettings']);
    Route::patch('/api/orders/{code}', [AdminController::class, 'changeOrder']);
    Route::post('/api/pos', [AdminController::class, 'cashSale'])->middleware('throttle:20,1');
    Route::post('/api/promos', [AdminController::class, 'savePromotion']);
    Route::put('/api/promos/{id}', [AdminController::class, 'savePromotion']);
    Route::delete('/api/promos/{id}', [AdminController::class, 'deletePromotion']);
});
