<?php

use App\Http\Controllers\Admin\ReportCsvController;
use App\Http\Controllers\Admin\ReportPdfController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

// Exportação de relatórios em PDF usando a sessão do painel e o tenant explícito.
Route::get('admin/{organization:slug}/reports/pdf', ReportPdfController::class)
    ->middleware('auth')
    ->name('reports.pdf');

Route::get('admin/{organization:slug}/reports/csv', ReportCsvController::class)
    ->middleware('auth')
    ->name('reports.csv');
