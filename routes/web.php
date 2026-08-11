<?php

use App\Http\Controllers\Admin\ReportPdfController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

// Exportação de relatórios em PDF (usa a sessão do painel; checa is_staff no controller).
Route::get('reports/pdf', ReportPdfController::class)
    ->middleware('auth')
    ->name('reports.pdf');
