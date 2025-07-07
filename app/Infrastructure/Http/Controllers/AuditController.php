<?php

namespace App\Infrastructure\Http\Controllers;

use App\Domain\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Routing\Controller;

class AuditController extends Controller
{
    public function logs(Request $request): View
    {
        $query = ActivityLog::with('user')
            ->orderBy('created_at', 'desc');

        // Filtro por módulo
        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }

        // Filtro por usuario
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Si no viene date_from, setearlo al día de hoy por defecto
        $dateFrom = $request->filled('date_from') ? $request->date_from : now()->format('Y-m-d');
        $query->whereDate('created_at', '>=', $dateFrom);

        $dateTo = $request->filled('date_to') ? $request->date_to : now()->format('Y-m-d');
        $query->whereDate('created_at', '<=', $dateTo);

        // Filtro por fecha hasta
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filtro por acción
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Filtro por descripción
        if ($request->filled('description')) {
            $query->where('description', 'like', '%' . $request->description . '%');
        }

        $logs = $query->paginate(25);

        // Obtener datos para filtros
        $modules = ActivityLog::distinct()->pluck('module')->filter();
        $users = \App\Domain\Models\User::orderBy('name')->get();
        $actions = ActivityLog::distinct()->pluck('action')->filter();

        return view('audit.logs', compact('logs', 'modules', 'users', 'actions'));
    }
} 