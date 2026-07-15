<?php

namespace App\Http\Controllers;

use App\Models\AgentSchedule;
use App\Models\Website;
use App\Services\Agents\AgentScheduleRunner;
use App\Services\Agents\AgentScheduleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class AgentScheduleController extends Controller
{
    public function index(Request $request): View
    {
        return view('agent-schedules.index', ['schedules' => AgentSchedule::with(['website', 'agent'])->when($request->website_id, fn ($query) => $query->where('website_id', $request->website_id))->orderBy('next_run_at')->paginate(30)->withQueryString(), 'websites' => Website::where('status', 'active')->orderBy('name')->get()]);
    }

    public function update(Request $request, AgentSchedule $agentSchedule, AgentScheduleService $service): RedirectResponse
    {
        $data = $request->validate(['frequency' => ['required', Rule::in(AgentScheduleService::FREQUENCIES)], 'timezone' => ['nullable', 'timezone'], 'run_at' => ['nullable', 'date_format:H:i'], 'weekday' => ['nullable', 'integer', 'between:0,6']]);
        if (! $service->preventDuplicateSchedule($agentSchedule->website, $agentSchedule->agent, $agentSchedule->schedule_type, $data['frequency'], $agentSchedule->id)) return back()->with('error', 'An enabled matching schedule already exists.');
        $service->updateSchedule($agentSchedule, ['frequency' => $data['frequency'], 'timezone' => $data['timezone'] ?: null, 'run_at' => $data['run_at'] ?: null, 'settings' => [...($agentSchedule->settings ?? []), 'weekday' => (int) ($data['weekday'] ?? 1)]]);
        return back()->with('success', 'Agent schedule updated.');
    }

    public function toggle(AgentSchedule $agentSchedule, AgentScheduleService $service): RedirectResponse
    {
        $wasEnabled = $agentSchedule->enabled;
        try { $wasEnabled ? $service->disable($agentSchedule) : $service->enable($agentSchedule->loadMissing(['website', 'agent'])); }
        catch (\InvalidArgumentException $exception) { return back()->with('error', $exception->getMessage()); }
        return back()->with('success', 'Schedule '.($wasEnabled ? 'disabled' : 'enabled').'.');
    }

    public function run(AgentSchedule $agentSchedule, AgentScheduleService $service, AgentScheduleRunner $runner): RedirectResponse
    {
        try { $service->markRunning($agentSchedule); $runner->run($agentSchedule->fresh(['website', 'agent'])); $service->markCompleted($agentSchedule); }
        catch (Throwable) { $service->markFailed($agentSchedule); return back()->with('error', 'Schedule could not run. Review the latest run status and application log.'); }
        return back()->with('success', 'Schedule completed. Agent actions remain pending review.');
    }

    public function defaults(Website $website, AgentScheduleService $service): RedirectResponse
    {
        $service->createDefaultSchedules($website);
        return back()->with('success', 'Missing default schedules created. Existing schedules were kept.');
    }
}
