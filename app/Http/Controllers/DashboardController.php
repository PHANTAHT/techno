<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        // Scope filter: task yang deadline-nya relevan
        // (tidak ada deadline, belum lewat, atau baru lewat max 7 hari)
        $relevantDeadline = function ($q) {
            $q->whereNull('deadline')
              ->orWhere('deadline', '>=', now()->subDays(7));
        };

        $urgentCount = Task::where('user_id', $userId)
                          ->where('status', '!=', 'done')
                          ->where($relevantDeadline)
                          ->where('deadline', '<=', now()->addDays(3))
                          ->whereNotNull('deadline')
                          ->count();

        $upcomingCount = Task::where('user_id', $userId)
                            ->where('status', '!=', 'done')
                            ->where($relevantDeadline)
                            ->count();

        $completedCount = Task::where('user_id', $userId)
                             ->where('status', 'done')
                             ->count();

        $nextTask = Task::where('user_id', $userId)
                       ->where('status', '!=', 'done')
                       ->where('deadline', '>=', now())
                       ->orderBy('deadline')
                       ->first();

        $recentTasks = Task::where('user_id', $userId)
                          ->where('status', '!=', 'done')
                          ->where($relevantDeadline)
                          ->orderBy('deadline')
                          ->take(5)
                          ->get();

        // Semua task untuk kalender (termasuk yang done, tanpa filter deadline)
        $tasks = Task::where('user_id', $userId)->get();

        // Ambil email dari Google
        try {
            $googleCtrl = new GoogleController();
            $emails     = $googleCtrl->fetchEmails();
            if (!is_array($emails)) $emails = [];
        } catch (\Exception $e) {
            $emails = [];
        }

        return view('dashboard', compact(
            'urgentCount', 'upcomingCount', 'nextTask',
            'completedCount', 'recentTasks', 'tasks', 'emails'
        ));
    }
    public function apiLatestTasks()
    {
        $userId = Auth::id();
        $relevantDeadline = function ($q) {
            $q->whereNull('deadline')->orWhere('deadline', '>=', now()->subDays(60));
        };

        $tasks = Task::where('user_id', $userId)
                    ->where('status', '!=', 'done')
                    ->where($relevantDeadline)
                    ->orderBy('deadline')->take(5)->get()
                    ->map(fn($t) => [
                        'id'          => $t->id,
                        'title'       => $t->title,
                        'course_name' => $t->course_name,
                        'source'      => $t->source,
                        'deadline'    => $t->deadline?->format('d M Y, H:i'),
                        'is_urgent'   => $t->isUrgent(),
                        'url'         => route('tasks.show', $t->id),
                    ]);

        return response()->json(['success' => true, 'tasks' => $tasks]);
    }
}