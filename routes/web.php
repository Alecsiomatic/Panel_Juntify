<?php

use App\Http\Controllers\AssistantController;
use App\Http\Controllers\AssistantSettingsController;
use App\Http\Controllers\CompanyGroupController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\MeetingDetailsController;
use App\Http\Controllers\MeetingGroupController;
use App\Http\Controllers\JuntifyMeetingController;
use App\Http\Controllers\MembersManagementController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect()->route('login');
});

// Dashboard DDU routes - Protegidas con autenticación Juntify
Route::middleware(['juntify.auth'])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/reuniones', [App\Http\Controllers\DashboardController::class, 'reuniones'])->name('reuniones.index');
    Route::get('/reuniones/detalles', [JuntifyMeetingController::class, 'showDetails'])->name('reuniones.showDetails');
    Route::get('/reuniones/{meeting}', [JuntifyMeetingController::class, 'show'])->name('reuniones.show');
    Route::post('/reuniones/{meeting}/grupos', [MeetingGroupController::class, 'attachMeeting'])->name('grupos.meetings.attach');
    Route::get('/download/audio/{meeting}', [App\Http\Controllers\JuntifyDownloadController::class, 'downloadAudio'])->name('download.audio');
    Route::get('/download/ju/{meeting}', [App\Http\Controllers\JuntifyDownloadController::class, 'downloadJu'])->name('download.ju');
    Route::get('/meeting-details/{transcriptionId}', [JuntifyMeetingController::class, 'showDetails'])->name('meetings.details');

    // Tareas - Kanban y Calendario
    Route::prefix('tareas')->name('tareas.')->group(function () {
        Route::get('/', [TaskController::class, 'index'])->name('index');
        Route::get('/list', [TaskController::class, 'list'])->name('list');
        Route::post('/', [TaskController::class, 'store'])->name('store');
        Route::put('/{taskId}', [TaskController::class, 'update'])->name('update');
        Route::delete('/{taskId}', [TaskController::class, 'destroy'])->name('destroy');
        Route::post('/{taskId}/complete', [TaskController::class, 'complete'])->name('complete');
    });

    Route::get('/grupos', [MeetingGroupController::class, 'index'])->name('grupos.index');
    Route::post('/grupos', [MeetingGroupController::class, 'store'])->name('grupos.store');
    Route::delete('/grupos/{group}', [MeetingGroupController::class, 'destroy'])->name('grupos.destroy');
    Route::post('/grupos/{group}/miembros', [MeetingGroupController::class, 'storeMember'])->name('grupos.members.store');
    Route::delete('/grupos/{group}/meetings/{meeting}', [MeetingGroupController::class, 'detachMeeting'])->name('grupos.meetings.detach');
    Route::prefix('asistente')->name('assistant.')->group(function () {
        Route::get('/', [AssistantController::class, 'index'])->name('index');
        Route::get('/configuracion', [AssistantSettingsController::class, 'index'])->name('settings.index');
        Route::post('/mensaje', [AssistantController::class, 'sendMessage'])->name('message');
        Route::post('/conversaciones', [AssistantController::class, 'createConversation'])->name('conversations.create');
        Route::get('/conversaciones/{conversationId}', [AssistantController::class, 'showConversation'])->name('conversations.show');
        Route::delete('/conversaciones/{conversationId}', [AssistantController::class, 'deleteConversation'])->name('conversations.delete');
        Route::put('/conversaciones/{conversationId}', [AssistantController::class, 'updateConversation'])->name('conversations.update');
        Route::post('/documentos', [AssistantController::class, 'uploadDocument'])->name('documents.store');
        Route::post('/configuracion', [AssistantSettingsController::class, 'update'])->name('settings.update');
    });

    // Rutas para administración de miembros (solo administradores)
    Route::prefix('admin/members')->name('admin.members.')->group(function () {
        Route::get('/', [MembersManagementController::class, 'index'])->name('index');
        Route::get('/search', [MembersManagementController::class, 'searchUsers'])->name('search');
        Route::post('/add', [MembersManagementController::class, 'addMember'])->name('add');
        Route::patch('/{userId}/role', [MembersManagementController::class, 'updateRole'])->name('updateRole');
        Route::delete('/{userId}', [MembersManagementController::class, 'removeMember'])->name('remove');
    });

    // Rutas para grupos de empresa (API Juntify)
    Route::prefix('api/company-groups')->name('company-groups.')->group(function () {
        Route::get('/', [CompanyGroupController::class, 'index'])->name('index');
        Route::post('/', [CompanyGroupController::class, 'store'])->name('store');
        Route::get('/user', [CompanyGroupController::class, 'userGroups'])->name('user');
        Route::get('/members', [CompanyGroupController::class, 'companyMembers'])->name('members');
        Route::get('/{grupoId}', [CompanyGroupController::class, 'show'])->name('show');
        Route::put('/{grupoId}', [CompanyGroupController::class, 'update'])->name('update');
        Route::delete('/{grupoId}', [CompanyGroupController::class, 'destroy'])->name('destroy');
        Route::post('/{grupoId}/members', [CompanyGroupController::class, 'addMember'])->name('addMember');
        Route::put('/{grupoId}/members/{memberId}/role', [CompanyGroupController::class, 'updateMemberRole'])->name('updateMemberRole');
        Route::delete('/{grupoId}/members/{memberId}', [CompanyGroupController::class, 'removeMember'])->name('removeMember');
        Route::post('/{grupoId}/share-meeting', [CompanyGroupController::class, 'shareMeeting'])->name('shareMeeting');
        Route::get('/{grupoId}/shared-meetings', [CompanyGroupController::class, 'sharedMeetings'])->name('sharedMeetings');
        Route::delete('/{grupoId}/shared-meetings/{meetingId}', [CompanyGroupController::class, 'unshareMeeting'])->name('unshareMeeting');
        Route::get('/{grupoId}/shared-meetings/{meetingId}/files', [CompanyGroupController::class, 'downloadSharedMeetingFiles'])->name('downloadSharedFiles');
        Route::get('/{grupoId}/shared-meetings/{meetingId}/details', [CompanyGroupController::class, 'getSharedMeetingDetails'])->name('sharedMeetingDetails');
    });

    // Rutas para detalles de reuniones desde Juntify API
    Route::get('/api/meetings/{meetingId}/details', [MeetingDetailsController::class, 'showFromJuntify'])->name('meetings.juntify.details');
});

// Rutas de perfil (también protegidas con autenticación Juntify)
Route::middleware(['juntify.auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
