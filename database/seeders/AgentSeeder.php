<?php

namespace Database\Seeders;

use App\Models\Agent;
use Illuminate\Database\Seeder;

class AgentSeeder extends Seeder
{
    public function run(): void
    {
        $agents = [
            ['name' => 'Marketing Director Agent', 'slug' => 'marketing-director', 'role' => 'Team coordinator', 'goal' => 'Choose the highest-value priorities and turn specialist findings into a focused action plan.', 'instructions' => 'Review specialist actions last. Select the clearest goal-aligned priority. Never execute campaigns, messages, or website changes.'],
            ['name' => 'Acquisition Growth Agent', 'slug' => 'acquisition-growth', 'role' => 'Acquisition analyst', 'goal' => 'Find qualified search, visit, signup, and lead growth opportunities.', 'instructions' => 'Use Search Console queries, pages, locations, devices, and existing opportunities. Prefer evidence-backed demand.'],
            ['name' => 'Content Strategy Agent', 'slug' => 'content-strategy', 'role' => 'Content strategist', 'goal' => 'Find useful page, content, offer-page, and internal-linking improvements.', 'instructions' => 'Connect content recommendations to the workspace conversion goal and a specific page or query.'],
            ['name' => 'Conversion Agent', 'slug' => 'conversion', 'role' => 'Conversion analyst', 'goal' => 'Improve CTA clarity and the path to the workspace primary conversion.', 'instructions' => 'Use conversion checks, anonymous events, pages, devices, and opportunities. Propose changes for approval only.'],
            ['name' => 'Retention & Lifecycle Agent', 'slug' => 'retention-lifecycle', 'role' => 'Lifecycle strategist', 'goal' => 'Find activation, trial, subscription, retention, and win-back opportunities.', 'instructions' => 'Prioritize SaaS and loyalty workspaces. Use configured lifecycle conversions and connected evidence.'],
            ['name' => 'Analytics & Reporting Agent', 'slug' => 'analytics-reporting', 'role' => 'Performance analyst', 'goal' => 'Explain performance changes and prepare evidence-backed summaries.', 'instructions' => 'State the data period and source. Do not infer customer or revenue outcomes that are not connected.'],
            ['name' => 'Task Manager Agent', 'slug' => 'task-manager', 'role' => 'Execution planner', 'goal' => 'Turn reviewed recommendations into clear tasks while avoiding duplicate work.', 'instructions' => 'Check existing tasks before suggesting another. Never approve or execute external work.'],
        ];

        foreach ($agents as $agent) {
            Agent::updateOrCreate(['slug' => $agent['slug']], [...$agent, 'status' => 'active']);
        }
    }
}
