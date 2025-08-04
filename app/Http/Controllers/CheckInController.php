<?php

namespace App\Http\Controllers;

use App\Enums\CheckInStatus;
use App\Enums\UserType;
use App\Http\Requests\CreateCheckInRequest;
use App\Http\Requests\MarkCheckInCompleteRequest;
use App\Http\Requests\UpdateCheckInRequest;
use App\Models\CheckIn;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CheckInController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $query = CheckIn::with(['team', 'assignedUser', 'createdBy']);

        // Filter based on user role
        if ($user->user_type === UserType::OWNER) {
            // Organization owners see all check-ins in their organization
            $query->forOrganization($user->organization);
        } elseif ($user->user_type === UserType::ADMIN) {
            // Team admins see check-ins for their teams
            $query->whereHas('team', function ($q) use ($user) {
                $q->where('organization_id', $user->organization_id);
            });
        } else {
            // Regular members see only their assigned check-ins
            $query->forUser($user);
        }

        // Apply filters
        if ($request->filled('status')) {
            $status = CheckInStatus::from($request->status);
            $query->where('status', $status);
        }

        if ($request->filled('team_id')) {
            $query->where('team_id', $request->team_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'scheduled_date');
        $sortDirection = $request->get('sort_direction', 'asc');
        $query->orderBy($sortBy, $sortDirection);

        $checkIns = $query->paginate(15)->withQueryString();

        // Get teams for filter dropdown
        $teams = Team::where('organization_id', $user->organization_id)->get();

        // Get statistics
        $stats = $this->getStats($user);

        return Inertia::render('CheckIns/Index', [
            'checkIns' => $checkIns,
            'teams' => $teams,
            'stats' => $stats,
            'filters' => $request->only(['status', 'team_id', 'search', 'sort_by', 'sort_direction']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response
    {
        $this->authorize('create', CheckIn::class);
        
        $user = $request->user();
        $teams = Team::where('organization_id', $user->organization_id)->get();
        $users = User::where('organization_id', $user->organization_id)->get();

        return Inertia::render('CheckIns/Create', [
            'teams' => $teams,
            'users' => $users,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateCheckInRequest $request)
    {
        $this->authorize('create', CheckIn::class);
        
        $validated = $request->validated();
        
        CheckIn::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'team_id' => $validated['team_id'],
            'assigned_user_id' => $validated['assigned_user_id'],
            'created_by_user_id' => $request->user()->id,
            'scheduled_date' => $validated['scheduled_date'],
            'status' => CheckInStatus::PENDING,
        ]);

        return redirect()->route('check-ins.index')
            ->with('success', 'Check-in created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(CheckIn $checkIn): Response
    {
        $this->authorize('view', $checkIn);

        $checkIn->load(['team', 'assignedUser', 'createdBy']);

        return Inertia::render('CheckIns/Show', [
            'checkIn' => $checkIn,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CheckIn $checkIn, Request $request): Response
    {
        $this->authorize('update', $checkIn);

        $user = $request->user();
        $teams = Team::where('organization_id', $user->organization_id)->get();
        $users = User::where('organization_id', $user->organization_id)->get();

        $checkIn->load(['team', 'assignedUser', 'createdBy']);

        return Inertia::render('CheckIns/Edit', [
            'checkIn' => $checkIn,
            'teams' => $teams,
            'users' => $users,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCheckInRequest $request, CheckIn $checkIn)
    {
        $checkIn->update($request->validated());

        return redirect()->route('check-ins.show', $checkIn)
            ->with('success', 'Check-in updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CheckIn $checkIn)
    {
        $this->authorize('delete', $checkIn);

        $checkIn->delete();

        return redirect()->route('check-ins.index')
            ->with('success', 'Check-in deleted successfully.');
    }

    /**
     * Mark the check-in as completed.
     */
    public function markComplete(MarkCheckInCompleteRequest $request, CheckIn $checkIn)
    {
        $validated = $request->validated();
        
        $checkIn->markAsCompleted($validated['notes'] ?? null);

        return redirect()->route('check-ins.show', $checkIn)
            ->with('success', 'Check-in marked as completed.');
    }

    /**
     * Mark the check-in as in progress.
     */
    public function markInProgress(CheckIn $checkIn)
    {
        $this->authorize('markInProgress', $checkIn);

        $checkIn->markAsInProgress();

        return redirect()->route('check-ins.show', $checkIn)
            ->with('success', 'Check-in marked as in progress.');
    }

    /**
     * Get statistics for the dashboard.
     */
    private function getStats(User $user): array
    {
        $query = CheckIn::query();

        // Filter based on user role
        if ($user->user_type === UserType::OWNER) {
            $query->forOrganization($user->organization);
        } elseif ($user->user_type === UserType::ADMIN) {
            $query->whereHas('team', function ($q) use ($user) {
                $q->where('organization_id', $user->organization_id);
            });
        } else {
            $query->forUser($user);
        }

        // Use a single query with conditional aggregation to get all stats at once
        $stats = $query->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN scheduled_date < ? AND status != ? THEN 1 ELSE 0 END) as overdue
        ', [
            CheckInStatus::PENDING->value,
            CheckInStatus::IN_PROGRESS->value,
            CheckInStatus::COMPLETED->value,
            now()->toDateString(),
            CheckInStatus::COMPLETED->value,
        ])->first();

        $total = $stats->total ?? 0;
        $completed = $stats->completed ?? 0;

        return [
            'total' => $total,
            'pending' => $stats->pending ?? 0,
            'in_progress' => $stats->in_progress ?? 0,
            'completed' => $completed,
            'overdue' => $stats->overdue ?? 0,
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
        ];
    }
}
