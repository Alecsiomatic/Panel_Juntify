@extends('layouts.dashboard')

@section('page-title', 'Tareas')
@section('page-description', 'Gestiona y organiza tus tareas de reuniones')

@push('styles')
<style>
    /* Apple-inspired design with DDU theme */
    :root {
        --ddu-lavanda: #7C3AED;
        --ddu-lavanda-light: #A78BFA;
        --ddu-aqua: #06B6D4;
        --ddu-aqua-light: #67E8F9;
        --apple-gray: #F5F5F7;
        --apple-card: rgba(255, 255, 255, 0.8);
        --shadow-soft: 0 4px 30px rgba(0, 0, 0, 0.08);
        --shadow-hover: 0 8px 40px rgba(0, 0, 0, 0.12);
    }

    /* View Toggle */
    .view-toggle {
        background: var(--apple-gray);
        border-radius: 12px;
        padding: 4px;
        display: inline-flex;
        gap: 4px;
    }

    .view-toggle-btn {
        padding: 8px 20px;
        border-radius: 8px;
        font-weight: 500;
        font-size: 14px;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
        background: transparent;
        color: #666;
    }

    .view-toggle-btn.active {
        background: white;
        color: var(--ddu-lavanda);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    /* Kanban Board */
    .kanban-container {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 24px;
        padding: 20px 0;
    }

    .kanban-column {
        background: var(--apple-card);
        backdrop-filter: blur(20px);
        border-radius: 20px;
        padding: 20px;
        min-height: 500px;
        box-shadow: var(--shadow-soft);
        border: 1px solid rgba(255, 255, 255, 0.5);
    }

    .kanban-column-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
        padding-bottom: 16px;
        border-bottom: 1px solid #E5E7EB;
    }

    .kanban-column-title {
        font-size: 16px;
        font-weight: 600;
        color: #1F2937;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .kanban-column-count {
        background: #E5E7EB;
        color: #6B7280;
        font-size: 12px;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 20px;
    }

    .column-pending .kanban-column-count { background: #FEF3C7; color: #D97706; }
    .column-progress .kanban-column-count { background: #DBEAFE; color: #2563EB; }
    .column-completed .kanban-column-count { background: #D1FAE5; color: #059669; }

    .kanban-cards {
        display: flex;
        flex-direction: column;
        gap: 12px;
        min-height: 400px;
    }

    /* Task Card - Apple Style */
    .task-card {
        background: white;
        border-radius: 16px;
        padding: 16px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        border: 1px solid #F3F4F6;
        cursor: grab;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }

    .task-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-hover);
        border-color: var(--ddu-lavanda-light);
    }

    .task-card:active {
        cursor: grabbing;
        transform: scale(0.98);
    }

    .task-card.dragging {
        opacity: 0.5;
        transform: rotate(3deg);
    }

    .task-card-priority {
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        border-radius: 16px 0 0 16px;
    }

    .priority-alta { background: linear-gradient(180deg, #EF4444, #DC2626); }
    .priority-media { background: linear-gradient(180deg, #F59E0B, #D97706); }
    .priority-baja { background: linear-gradient(180deg, #10B981, #059669); }

    .task-card-content {
        padding-left: 12px;
    }

    .task-card-title {
        font-size: 14px;
        font-weight: 600;
        color: #1F2937;
        margin-bottom: 8px;
        line-height: 1.4;
    }

    .task-card-meta {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 12px;
        color: #9CA3AF;
    }

    .task-card-meta svg {
        width: 14px;
        height: 14px;
    }

    .task-card-due {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .task-card-due.overdue {
        color: #EF4444;
    }

    .task-card-assignee {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .task-card-avatar {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--ddu-lavanda), var(--ddu-aqua));
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        font-weight: 600;
        color: white;
    }

    .task-card-meeting {
        display: flex;
        align-items: center;
        gap: 4px;
        color: var(--ddu-lavanda);
    }

    .task-card-actions {
        position: absolute;
        top: 8px;
        right: 8px;
        display: flex;
        gap: 4px;
        opacity: 0;
        transition: opacity 0.2s;
    }

    .task-card:hover .task-card-actions {
        opacity: 1;
    }

    .task-action-btn {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        border: none;
        background: #F3F4F6;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }

    .task-action-btn:hover {
        background: var(--ddu-lavanda);
        color: white;
    }

    .task-action-btn svg {
        width: 14px;
        height: 14px;
    }

    /* Calendar View - Apple Style */
    .calendar-container {
        display: none;
        padding: 20px 0;
    }

    .calendar-container.active {
        display: block;
    }

    .kanban-container.active {
        display: grid;
    }

    .calendar-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 24px;
    }

    .calendar-nav {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .calendar-nav-btn {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        border: none;
        background: white;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: var(--shadow-soft);
        transition: all 0.2s;
    }

    .calendar-nav-btn:hover {
        background: var(--ddu-lavanda);
        color: white;
    }

    .calendar-current-month {
        font-size: 24px;
        font-weight: 600;
        color: #1F2937;
    }

    .calendar-grid {
        background: var(--apple-card);
        backdrop-filter: blur(20px);
        border-radius: 24px;
        padding: 24px;
        box-shadow: var(--shadow-soft);
        border: 1px solid rgba(255, 255, 255, 0.5);
    }

    .calendar-weekdays {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 8px;
        margin-bottom: 16px;
    }

    .calendar-weekday {
        text-align: center;
        font-size: 12px;
        font-weight: 600;
        color: #9CA3AF;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 8px;
    }

    .calendar-days {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 8px;
    }

    .calendar-day {
        min-height: 100px;
        background: white;
        border-radius: 16px;
        padding: 8px;
        border: 2px solid transparent;
        transition: all 0.2s;
        cursor: pointer;
    }

    .calendar-day:hover {
        border-color: var(--ddu-lavanda-light);
        transform: scale(1.02);
    }

    .calendar-day.today {
        border-color: var(--ddu-lavanda);
        background: linear-gradient(135deg, rgba(124, 58, 237, 0.05), rgba(6, 182, 212, 0.05));
    }

    .calendar-day.other-month {
        opacity: 0.4;
    }

    .calendar-day-number {
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
    }

    .calendar-day.today .calendar-day-number {
        color: var(--ddu-lavanda);
    }

    .calendar-day-tasks {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .calendar-task-item {
        font-size: 11px;
        padding: 4px 8px;
        border-radius: 6px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        cursor: pointer;
        transition: all 0.2s;
    }

    .calendar-task-item:hover {
        transform: scale(1.02);
    }

    .calendar-task-item.priority-alta {
        background: #FEE2E2;
        color: #DC2626;
    }

    .calendar-task-item.priority-media {
        background: #FEF3C7;
        color: #D97706;
    }

    .calendar-task-item.priority-baja {
        background: #D1FAE5;
        color: #059669;
    }

    .calendar-task-more {
        font-size: 10px;
        color: #6B7280;
        text-align: center;
        padding-top: 4px;
    }

    /* Add Task Button */
    .add-task-btn {
        position: fixed;
        bottom: 32px;
        right: 32px;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--ddu-lavanda), var(--ddu-aqua));
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 8px 32px rgba(124, 58, 237, 0.4);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 100;
    }

    .add-task-btn:hover {
        transform: scale(1.1) rotate(90deg);
        box-shadow: 0 12px 40px rgba(124, 58, 237, 0.5);
    }

    .add-task-btn svg {
        width: 28px;
        height: 28px;
        color: white;
    }

    /* Task Modal - Apple Style */
    .task-modal {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(8px);
        z-index: 1000;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .task-modal.show {
        display: flex;
        animation: modalFadeIn 0.3s ease;
    }

    @keyframes modalFadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }

    .task-modal-content {
        background: white;
        border-radius: 24px;
        width: 100%;
        max-width: 500px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        animation: modalSlideIn 0.3s ease;
    }

    @keyframes modalSlideIn {
        from {
            transform: translateY(20px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .task-modal-header {
        padding: 24px 24px 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .task-modal-title {
        font-size: 20px;
        font-weight: 600;
        color: #1F2937;
    }

    .task-modal-close {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        border: none;
        background: #F3F4F6;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }

    .task-modal-close:hover {
        background: #E5E7EB;
    }

    .task-modal-body {
        padding: 24px;
    }

    .task-form-group {
        margin-bottom: 20px;
    }

    .task-form-label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
    }

    .task-form-input,
    .task-form-textarea,
    .task-form-select {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #E5E7EB;
        border-radius: 12px;
        font-size: 14px;
        transition: all 0.2s;
        background: #FAFAFA;
    }

    .task-form-input:focus,
    .task-form-textarea:focus,
    .task-form-select:focus {
        outline: none;
        border-color: var(--ddu-lavanda);
        background: white;
        box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.1);
    }

    .task-form-textarea {
        min-height: 100px;
        resize: vertical;
    }

    .task-form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    .task-priority-options {
        display: flex;
        gap: 12px;
    }

    .task-priority-option {
        flex: 1;
        padding: 12px;
        border: 2px solid #E5E7EB;
        border-radius: 12px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s;
        font-size: 13px;
        font-weight: 500;
    }

    .task-priority-option:hover {
        border-color: #D1D5DB;
    }

    .task-priority-option.selected {
        border-color: var(--ddu-lavanda);
        background: rgba(124, 58, 237, 0.05);
    }

    .task-priority-option.priority-alta.selected {
        border-color: #EF4444;
        background: #FEE2E2;
    }

    .task-priority-option.priority-media.selected {
        border-color: #F59E0B;
        background: #FEF3C7;
    }

    .task-priority-option.priority-baja.selected {
        border-color: #10B981;
        background: #D1FAE5;
    }

    .task-modal-footer {
        padding: 0 24px 24px;
        display: flex;
        gap: 12px;
    }

    .task-btn {
        flex: 1;
        padding: 14px 24px;
        border-radius: 12px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        border: none;
    }

    .task-btn-cancel {
        background: #F3F4F6;
        color: #374151;
    }

    .task-btn-cancel:hover {
        background: #E5E7EB;
    }

    .task-btn-save {
        background: linear-gradient(135deg, var(--ddu-lavanda), var(--ddu-aqua));
        color: white;
    }

    .task-btn-save:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(124, 58, 237, 0.4);
    }

    .task-btn-delete {
        background: #FEE2E2;
        color: #DC2626;
    }

    .task-btn-delete:hover {
        background: #FECACA;
    }

    /* Stats Cards */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }

    .stat-card {
        background: var(--apple-card);
        backdrop-filter: blur(20px);
        border-radius: 16px;
        padding: 20px;
        box-shadow: var(--shadow-soft);
        border: 1px solid rgba(255, 255, 255, 0.5);
        transition: all 0.3s;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-hover);
    }

    .stat-card-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 12px;
    }

    .stat-card-icon svg {
        width: 24px;
        height: 24px;
        color: white;
    }

    .stat-card-value {
        font-size: 28px;
        font-weight: 700;
        color: #1F2937;
        line-height: 1;
    }

    .stat-card-label {
        font-size: 13px;
        color: #6B7280;
        margin-top: 4px;
    }

    .stat-icon-total { background: linear-gradient(135deg, var(--ddu-lavanda), var(--ddu-aqua)); }
    .stat-icon-pending { background: linear-gradient(135deg, #F59E0B, #EAB308); }
    .stat-icon-progress { background: linear-gradient(135deg, #3B82F6, #6366F1); }
    .stat-icon-completed { background: linear-gradient(135deg, #10B981, #14B8A6); }

    /* Loading State */
    .loading-overlay {
        position: absolute;
        inset: 0;
        background: rgba(255, 255, 255, 0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 20px;
    }

    .loading-spinner {
        width: 40px;
        height: 40px;
        border: 3px solid #E5E7EB;
        border-top-color: var(--ddu-lavanda);
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #9CA3AF;
    }

    .empty-state svg {
        width: 48px;
        height: 48px;
        margin-bottom: 12px;
        opacity: 0.5;
    }

    .empty-state-text {
        font-size: 14px;
    }

    /* Responsive */
    @media (max-width: 1024px) {
        .kanban-container {
            grid-template-columns: 1fr;
        }

        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 640px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }

        .task-form-row {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Tareas</h1>
                <p class="text-gray-500 mt-1">Gestiona y organiza tus tareas de reuniones</p>
            </div>
            <div class="view-toggle">
                <button class="view-toggle-btn active" data-view="kanban">
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path>
                    </svg>
                    Kanban
                </button>
                <button class="view-toggle-btn" data-view="calendar">
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    Calendario
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-icon stat-icon-total">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
                <div class="stat-card-value" id="stat-total">0</div>
                <div class="stat-card-label">Total de tareas</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon stat-icon-pending">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="stat-card-value" id="stat-pending">0</div>
                <div class="stat-card-label">Pendientes</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon stat-icon-progress">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div class="stat-card-value" id="stat-progress">0</div>
                <div class="stat-card-label">En progreso</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon stat-icon-completed">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="stat-card-value" id="stat-completed">0</div>
                <div class="stat-card-label">Completadas</div>
            </div>
        </div>

        <!-- Kanban View -->
        <div class="kanban-container active" id="kanban-view">
            <!-- Pending Column -->
            <div class="kanban-column column-pending" data-status="pending">
                <div class="kanban-column-header">
                    <div class="kanban-column-title">
                        <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Pendientes
                    </div>
                    <span class="kanban-column-count" id="count-pending">0</span>
                </div>
                <div class="kanban-cards" id="cards-pending"></div>
            </div>

            <!-- In Progress Column -->
            <div class="kanban-column column-progress" data-status="in_progress">
                <div class="kanban-column-header">
                    <div class="kanban-column-title">
                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        En Progreso
                    </div>
                    <span class="kanban-column-count" id="count-progress">0</span>
                </div>
                <div class="kanban-cards" id="cards-progress"></div>
            </div>

            <!-- Completed Column -->
            <div class="kanban-column column-completed" data-status="completed">
                <div class="kanban-column-header">
                    <div class="kanban-column-title">
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Completadas
                    </div>
                    <span class="kanban-column-count" id="count-completed">0</span>
                </div>
                <div class="kanban-cards" id="cards-completed"></div>
            </div>
        </div>

        <!-- Calendar View -->
        <div class="calendar-container" id="calendar-view">
            <div class="calendar-header">
                <div class="calendar-nav">
                    <button class="calendar-nav-btn" id="prev-month">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </button>
                    <h2 class="calendar-current-month" id="current-month">Febrero 2026</h2>
                    <button class="calendar-nav-btn" id="next-month">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                </div>
                <button class="calendar-nav-btn" id="today-btn" style="width: auto; padding: 0 16px; font-size: 14px; font-weight: 500;">
                    Hoy
                </button>
            </div>
            <div class="calendar-grid">
                <div class="calendar-weekdays">
                    <div class="calendar-weekday">Dom</div>
                    <div class="calendar-weekday">Lun</div>
                    <div class="calendar-weekday">Mar</div>
                    <div class="calendar-weekday">Mié</div>
                    <div class="calendar-weekday">Jue</div>
                    <div class="calendar-weekday">Vie</div>
                    <div class="calendar-weekday">Sáb</div>
                </div>
                <div class="calendar-days" id="calendar-days"></div>
            </div>
        </div>
    </div>

    <!-- Add Task Button -->
    <button class="add-task-btn" id="add-task-btn" title="Nueva tarea">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
    </button>
</div>

<!-- Task Modal -->
<div class="task-modal" id="task-modal">
    <div class="task-modal-content">
        <div class="task-modal-header">
            <h3 class="task-modal-title" id="modal-title">Nueva Tarea</h3>
            <button class="task-modal-close" id="close-modal">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <form id="task-form">
            <input type="hidden" id="task-id" name="task_id">
            <div class="task-modal-body">
                <div class="task-form-group">
                    <label class="task-form-label">Título *</label>
                    <input type="text" class="task-form-input" id="task-title" name="title" placeholder="Escribe el título de la tarea" required>
                </div>
                <div class="task-form-group">
                    <label class="task-form-label">Descripción</label>
                    <textarea class="task-form-textarea" id="task-description" name="description" placeholder="Agrega una descripción detallada..."></textarea>
                </div>
                <div class="task-form-group">
                    <label class="task-form-label">Prioridad</label>
                    <div class="task-priority-options">
                        <div class="task-priority-option priority-baja" data-priority="baja">
                            <span>🟢 Baja</span>
                        </div>
                        <div class="task-priority-option priority-media selected" data-priority="media">
                            <span>🟡 Media</span>
                        </div>
                        <div class="task-priority-option priority-alta" data-priority="alta">
                            <span>🔴 Alta</span>
                        </div>
                    </div>
                    <input type="hidden" id="task-priority" name="priority" value="media">
                </div>
                <div class="task-form-row">
                    <div class="task-form-group">
                        <label class="task-form-label">Fecha límite</label>
                        <input type="date" class="task-form-input" id="task-due-date" name="due_date">
                    </div>
                    <div class="task-form-group">
                        <label class="task-form-label">Hora</label>
                        <input type="time" class="task-form-input" id="task-due-time" name="due_time">
                    </div>
                </div>
            </div>
            <div class="task-modal-footer">
                <button type="button" class="task-btn task-btn-delete" id="delete-task-btn" style="display: none;">Eliminar</button>
                <button type="button" class="task-btn task-btn-cancel" id="cancel-btn">Cancelar</button>
                <button type="submit" class="task-btn task-btn-save" id="save-btn">Guardar</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // State
    let tasks = [];
    let currentMonth = new Date().getMonth();
    let currentYear = new Date().getFullYear();
    let editingTaskId = null;

    // DOM Elements
    const kanbanView = document.getElementById('kanban-view');
    const calendarView = document.getElementById('calendar-view');
    const viewToggleBtns = document.querySelectorAll('.view-toggle-btn');
    const taskModal = document.getElementById('task-modal');
    const taskForm = document.getElementById('task-form');
    const addTaskBtn = document.getElementById('add-task-btn');
    const closeModalBtn = document.getElementById('close-modal');
    const cancelBtn = document.getElementById('cancel-btn');
    const deleteTaskBtn = document.getElementById('delete-task-btn');
    const priorityOptions = document.querySelectorAll('.task-priority-option');

    // API Endpoints
    const API = {
        list: '{{ route("tareas.list") }}',
        store: '{{ route("tareas.store") }}',
        update: (id) => `/tareas/${id}`,
        destroy: (id) => `/tareas/${id}`,
        complete: (id) => `/tareas/${id}/complete`
    };

    // Initialize
    loadTasks();

    // View Toggle
    viewToggleBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            viewToggleBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            const view = btn.dataset.view;
            if (view === 'kanban') {
                kanbanView.classList.add('active');
                calendarView.classList.remove('active');
            } else {
                kanbanView.classList.remove('active');
                calendarView.classList.add('active');
                renderCalendar();
            }
        });
    });

    // Load Tasks
    async function loadTasks() {
        try {
            const response = await fetch(API.list);
            
            if (!response.ok) {
                console.warn('API no disponible, mostrando vista vacía');
                tasks = [];
                renderKanban();
                return;
            }
            
            const data = await response.json();
            
            if (data.data && data.data.tasks) {
                tasks = data.data.tasks;
                updateStats(data.data.stats || {});
            } else if (data.tasks) {
                tasks = data.tasks;
                updateStats(data.stats || {});
            } else if (Array.isArray(data)) {
                tasks = data;
            } else {
                tasks = [];
            }
            
            renderKanban();
            if (calendarView.classList.contains('active')) {
                renderCalendar();
            }
        } catch (error) {
            console.warn('Error loading tasks (API may not be ready):', error);
            tasks = [];
            renderKanban();
        }
    }

    // Update Stats
    function updateStats(stats) {
        document.getElementById('stat-total').textContent = stats.total || tasks.length || 0;
        document.getElementById('stat-pending').textContent = stats.pending || 0;
        document.getElementById('stat-progress').textContent = stats.in_progress || 0;
        document.getElementById('stat-completed').textContent = stats.completed || 0;
    }

    // Render Kanban
    function renderKanban() {
        const pendingTasks = tasks.filter(t => t.status === 'pending' || t.progress === 0);
        const progressTasks = tasks.filter(t => t.status === 'in_progress' || (t.progress > 0 && t.progress < 100));
        const completedTasks = tasks.filter(t => t.status === 'completed' || t.progress >= 100);

        renderColumn('cards-pending', pendingTasks, 'count-pending');
        renderColumn('cards-progress', progressTasks, 'count-progress');
        renderColumn('cards-completed', completedTasks, 'count-completed');

        // Update stats from rendered tasks
        document.getElementById('stat-total').textContent = tasks.length;
        document.getElementById('stat-pending').textContent = pendingTasks.length;
        document.getElementById('stat-progress').textContent = progressTasks.length;
        document.getElementById('stat-completed').textContent = completedTasks.length;

        // Setup drag and drop
        setupDragAndDrop();
    }

    function renderColumn(containerId, columnTasks, countId) {
        const container = document.getElementById(containerId);
        const countEl = document.getElementById(countId);
        
        countEl.textContent = columnTasks.length;

        if (columnTasks.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    <p class="empty-state-text">No hay tareas aquí</p>
                </div>
            `;
            return;
        }

        container.innerHTML = columnTasks.map(task => createTaskCard(task)).join('');
    }

    function createTaskCard(task) {
        const priority = task.priority || 'media';
        const dueDate = task.due_date ? formatDate(task.due_date) : '';
        const isOverdue = task.is_overdue || (task.due_date && new Date(task.due_date) < new Date());
        const assignee = task.assignee?.name || task.assigned_to || '';
        const meeting = task.meeting?.name || '';

        return `
            <div class="task-card" draggable="true" data-task-id="${task.id}">
                <div class="task-card-priority priority-${priority}"></div>
                <div class="task-card-actions">
                    <button class="task-action-btn edit-task" data-task-id="${task.id}">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </button>
                    <button class="task-action-btn complete-task" data-task-id="${task.id}">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </button>
                </div>
                <div class="task-card-content">
                    <h4 class="task-card-title">${escapeHtml(task.title || task.task || task.tarea || '')}</h4>
                    <div class="task-card-meta">
                        ${dueDate ? `
                            <span class="task-card-due ${isOverdue ? 'overdue' : ''}">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                ${dueDate}
                            </span>
                        ` : ''}
                        ${assignee ? `
                            <span class="task-card-assignee">
                                <span class="task-card-avatar">${assignee.substring(0, 2).toUpperCase()}</span>
                                ${assignee.split(' ')[0]}
                            </span>
                        ` : ''}
                        ${meeting ? `
                            <span class="task-card-meeting">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857"></path>
                                </svg>
                            </span>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
    }

    // Drag and Drop
    function setupDragAndDrop() {
        const cards = document.querySelectorAll('.task-card');
        const columns = document.querySelectorAll('.kanban-cards');

        cards.forEach(card => {
            card.addEventListener('dragstart', (e) => {
                card.classList.add('dragging');
                e.dataTransfer.setData('text/plain', card.dataset.taskId);
            });

            card.addEventListener('dragend', () => {
                card.classList.remove('dragging');
            });

            // Click handlers
            card.querySelector('.edit-task')?.addEventListener('click', (e) => {
                e.stopPropagation();
                editTask(card.dataset.taskId);
            });

            card.querySelector('.complete-task')?.addEventListener('click', (e) => {
                e.stopPropagation();
                completeTask(card.dataset.taskId);
            });
        });

        columns.forEach(column => {
            column.addEventListener('dragover', (e) => {
                e.preventDefault();
                column.style.background = 'rgba(124, 58, 237, 0.05)';
            });

            column.addEventListener('dragleave', () => {
                column.style.background = '';
            });

            column.addEventListener('drop', async (e) => {
                e.preventDefault();
                column.style.background = '';
                
                const taskId = e.dataTransfer.getData('text/plain');
                const newStatus = column.parentElement.dataset.status;
                
                await updateTaskStatus(taskId, newStatus);
            });
        });
    }

    // Update Task Status
    async function updateTaskStatus(taskId, newStatus) {
        try {
            const response = await fetch(API.update(taskId), {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ status: newStatus })
            });

            if (response.ok) {
                loadTasks();
            }
        } catch (error) {
            console.error('Error updating task:', error);
        }
    }

    // Complete Task
    async function completeTask(taskId) {
        try {
            const response = await fetch(API.complete(taskId), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            if (response.ok) {
                loadTasks();
            }
        } catch (error) {
            console.error('Error completing task:', error);
        }
    }

    // Calendar
    const monthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

    document.getElementById('prev-month').addEventListener('click', () => {
        currentMonth--;
        if (currentMonth < 0) {
            currentMonth = 11;
            currentYear--;
        }
        renderCalendar();
    });

    document.getElementById('next-month').addEventListener('click', () => {
        currentMonth++;
        if (currentMonth > 11) {
            currentMonth = 0;
            currentYear++;
        }
        renderCalendar();
    });

    document.getElementById('today-btn').addEventListener('click', () => {
        currentMonth = new Date().getMonth();
        currentYear = new Date().getFullYear();
        renderCalendar();
    });

    function renderCalendar() {
        document.getElementById('current-month').textContent = `${monthNames[currentMonth]} ${currentYear}`;
        
        const firstDay = new Date(currentYear, currentMonth, 1);
        const lastDay = new Date(currentYear, currentMonth + 1, 0);
        const startingDay = firstDay.getDay();
        const monthDays = lastDay.getDate();
        
        const today = new Date();
        const todayStr = today.toISOString().split('T')[0];
        
        let html = '';
        
        // Previous month days
        const prevMonthLastDay = new Date(currentYear, currentMonth, 0).getDate();
        for (let i = startingDay - 1; i >= 0; i--) {
            const day = prevMonthLastDay - i;
            html += `<div class="calendar-day other-month"><div class="calendar-day-number">${day}</div></div>`;
        }
        
        // Current month days
        for (let day = 1; day <= monthDays; day++) {
            const dateStr = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const isToday = dateStr === todayStr;
            const dayTasks = tasks.filter(t => t.due_date === dateStr);
            
            html += `
                <div class="calendar-day ${isToday ? 'today' : ''}" data-date="${dateStr}">
                    <div class="calendar-day-number">${day}</div>
                    <div class="calendar-day-tasks">
                        ${dayTasks.slice(0, 3).map(t => `
                            <div class="calendar-task-item priority-${t.priority || 'media'}" data-task-id="${t.id}">
                                ${escapeHtml((t.title || t.task || t.tarea || '').substring(0, 20))}
                            </div>
                        `).join('')}
                        ${dayTasks.length > 3 ? `<div class="calendar-task-more">+${dayTasks.length - 3} más</div>` : ''}
                    </div>
                </div>
            `;
        }
        
        // Next month days
        const remainingDays = 42 - (startingDay + monthDays);
        for (let day = 1; day <= remainingDays; day++) {
            html += `<div class="calendar-day other-month"><div class="calendar-day-number">${day}</div></div>`;
        }
        
        document.getElementById('calendar-days').innerHTML = html;

        // Add click handlers for calendar tasks
        document.querySelectorAll('.calendar-task-item').forEach(item => {
            item.addEventListener('click', () => {
                editTask(item.dataset.taskId);
            });
        });

        // Add click handlers for calendar days - create new task
        document.querySelectorAll('.calendar-day:not(.other-month)').forEach(day => {
            day.addEventListener('dblclick', () => {
                openModal();
                document.getElementById('task-due-date').value = day.dataset.date;
            });
        });
    }

    // Modal
    addTaskBtn.addEventListener('click', openModal);
    closeModalBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);
    taskModal.addEventListener('click', (e) => {
        if (e.target === taskModal) closeModal();
    });

    function openModal() {
        editingTaskId = null;
        taskForm.reset();
        document.getElementById('modal-title').textContent = 'Nueva Tarea';
        deleteTaskBtn.style.display = 'none';
        
        // Reset priority
        priorityOptions.forEach(opt => opt.classList.remove('selected'));
        document.querySelector('[data-priority="media"]').classList.add('selected');
        document.getElementById('task-priority').value = 'media';
        
        taskModal.classList.add('show');
    }

    function closeModal() {
        taskModal.classList.remove('show');
        editingTaskId = null;
    }

    function editTask(taskId) {
        const task = tasks.find(t => t.id == taskId);
        if (!task) return;

        editingTaskId = taskId;
        document.getElementById('modal-title').textContent = 'Editar Tarea';
        document.getElementById('task-id').value = taskId;
        document.getElementById('task-title').value = task.title || task.task || task.tarea || '';
        document.getElementById('task-description').value = task.description || task.descripcion || '';
        document.getElementById('task-due-date').value = task.due_date || task.fecha_limite || '';
        document.getElementById('task-due-time').value = task.due_time || task.hora_limite || '';
        
        const priority = task.priority || task.prioridad || 'media';
        priorityOptions.forEach(opt => opt.classList.remove('selected'));
        document.querySelector(`[data-priority="${priority}"]`)?.classList.add('selected');
        document.getElementById('task-priority').value = priority;
        
        deleteTaskBtn.style.display = 'block';
        taskModal.classList.add('show');
    }

    // Priority Selection
    priorityOptions.forEach(opt => {
        opt.addEventListener('click', () => {
            priorityOptions.forEach(o => o.classList.remove('selected'));
            opt.classList.add('selected');
            document.getElementById('task-priority').value = opt.dataset.priority;
        });
    });

    // Form Submit
    taskForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = {
            title: document.getElementById('task-title').value,
            description: document.getElementById('task-description').value,
            priority: document.getElementById('task-priority').value,
            due_date: document.getElementById('task-due-date').value || null,
            due_time: document.getElementById('task-due-time').value || null
        };

        try {
            let response;
            if (editingTaskId) {
                response = await fetch(API.update(editingTaskId), {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(formData)
                });
            } else {
                response = await fetch(API.store, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(formData)
                });
            }

            if (response.ok) {
                closeModal();
                loadTasks();
            } else {
                const error = await response.json();
                alert(error.error || 'Error al guardar la tarea');
            }
        } catch (error) {
            console.error('Error saving task:', error);
            alert('Error al guardar la tarea');
        }
    });

    // Delete Task
    deleteTaskBtn.addEventListener('click', async () => {
        if (!editingTaskId) return;
        
        if (!confirm('¿Estás seguro de que deseas eliminar esta tarea?')) return;

        try {
            const response = await fetch(API.destroy(editingTaskId), {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            if (response.ok) {
                closeModal();
                loadTasks();
            }
        } catch (error) {
            console.error('Error deleting task:', error);
        }
    });

    // Helpers
    function formatDate(dateStr) {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        return date.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>
@endpush
