<?php

use App\Http\Controllers\Lab\CatalogueController;
use App\Http\Controllers\Lab\CriticalAlertController;
use App\Http\Controllers\Lab\DashboardController;
use App\Http\Controllers\Lab\InvoiceController;
use App\Http\Controllers\Lab\OrderController;
use App\Http\Controllers\Lab\PatientController;
use App\Http\Controllers\Lab\ReportController;
use App\Http\Controllers\Lab\SpecimenController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');
Route::get('verify/{token}', [ReportController::class, 'verify'])->name('reports.verify');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::middleware('lab.permission:patient.write')->group(function () {
        Route::get('patients/create', [PatientController::class, 'create'])->name('patients.create');
        Route::post('patients', [PatientController::class, 'store'])->name('patients.store');
    });
    Route::middleware('lab.permission:patient.read')->group(function () {
        Route::get('patients', [PatientController::class, 'index'])->name('patients.index');
        Route::get('patients/{patient}', [PatientController::class, 'show'])->name('patients.show');
    });

    Route::middleware('lab.permission:catalogue.read')->group(function () {
        Route::get('catalogue', [CatalogueController::class, 'index'])->name('catalogue.index');
        Route::get('catalogue/{test}', [CatalogueController::class, 'show'])->name('catalogue.show');
        Route::patch('catalogue/{test}', [CatalogueController::class, 'update'])->name('catalogue.update');
        Route::post('catalogue', [CatalogueController::class, 'store'])->name('catalogue.store');
        Route::post('catalogue/{test}/promote', [CatalogueController::class, 'promote'])->name('catalogue.promote');
    });

    Route::middleware('lab.permission:order.create')->group(function () {
        Route::get('orders/create', [OrderController::class, 'create'])->name('orders.create');
        Route::post('orders', [OrderController::class, 'store'])->name('orders.store');
    });
    Route::middleware('lab.permission:order.read')->group(function () {
        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    });
    Route::middleware('lab.permission:result.enter')->group(function () {
        Route::get('orders/{order}/results', [OrderController::class, 'results'])->name('orders.results');
        Route::post('orders/{order}/results', [OrderController::class, 'storeResults'])->name('orders.results.store');
    });
    Route::post('orders/{order}/review', [OrderController::class, 'review'])
        ->middleware('lab.permission:result.review')->name('orders.review');
    Route::post('orders/{order}/validate', [OrderController::class, 'validateOrder'])
        ->middleware('lab.permission:result.validate')->name('orders.validate');

    Route::middleware('lab.permission:specimen.read')->group(function () {
        Route::get('collection', [SpecimenController::class, 'collection'])->name('collection.index');
    });
    Route::post('specimens/{specimen}/collect', [SpecimenController::class, 'collect'])
        ->middleware('lab.permission:specimen.collect')->name('specimens.collect');
    Route::get('specimens/{specimen}/label', [SpecimenController::class, 'label'])
        ->middleware('lab.permission:barcode.print')->name('specimens.label');
    Route::middleware('lab.permission:specimen.receive')->group(function () {
        Route::get('accession', [SpecimenController::class, 'lookup'])->name('accession.index');
        Route::post('accession', [SpecimenController::class, 'scan'])->name('accession.scan');
        Route::post('specimens/{specimen}/receive', [SpecimenController::class, 'receive'])->name('specimens.receive');
    });

    Route::middleware('lab.permission:report.read')->group(function () {
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/{report}', [ReportController::class, 'show'])->name('reports.show');
        Route::post('orders/{order}/report', [ReportController::class, 'preview'])->name('reports.preview');
    });
    Route::post('orders/{order}/release', [ReportController::class, 'release'])
        ->middleware('lab.permission:report.release')->name('reports.release');
    Route::post('reports/{report}/correct', [ReportController::class, 'correct'])
        ->middleware('lab.permission:report.correct')->name('reports.correct');
    Route::post('reports/{report}/deliver', [ReportController::class, 'deliver'])
        ->middleware('lab.permission:report.release')->name('reports.deliver');
    Route::post('reports/{report}/withdraw', [ReportController::class, 'withdraw'])
        ->middleware('lab.permission:report.withdraw')->name('reports.withdraw');
    Route::get('reports/{report}/download', [ReportController::class, 'download'])
        ->middleware('lab.permission:report.download')->name('reports.download');

    Route::middleware('lab.permission:template.manage')->group(function () {
        Route::get('templates', [ReportController::class, 'templates'])->name('templates.index');
        Route::post('templates', [ReportController::class, 'storeTemplate'])->name('templates.store');
    });

    Route::get('critical', [CriticalAlertController::class, 'index'])
        ->middleware('lab.permission:result.read')->name('critical.index');
    Route::post('critical/{alert}/acknowledge', [CriticalAlertController::class, 'acknowledge'])
        ->middleware('lab.permission:critical.acknowledge')->name('critical.acknowledge');

    Route::post('invoices/{invoice}/pay', [InvoiceController::class, 'pay'])
        ->middleware('lab.permission:invoice.write')->name('invoices.pay');
});

require __DIR__.'/settings.php';
