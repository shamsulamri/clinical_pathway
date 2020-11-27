<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CPController;
use App\Http\Controllers\EditorController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});


Route::get('/cp/{soap}/{problem}/{section?}', [CPController::class, 'generate']);
Route::post('/cp/create', [CPController::class, 'create'])->name('cp.create');
Route::post('/cp/remove', [CPController::class, 'remove'])->name('cp.remove');

Route::resource('consultations', 'ConsultationController');
Route::post('/consultation/search{id?}', 'ConsultationController@search');
Route::get('/consultation/search/{id?}', 'ConsultationController@search');
Route::get('/consultation/delete/{id}', 'ConsultationController@delete');

Route::get('/editor', [EditorController::class, 'generate']);
Route::post('/editor/add', [EditorController::class, 'add'])->name('editor.add');

