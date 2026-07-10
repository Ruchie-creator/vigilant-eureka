<?php

namespace App\Http\Controllers;

use App\Models\GrowthOpportunity;
use App\Models\MarketingTask;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GrowthOpportunityController extends Controller
{
    public function storeTask(GrowthOpportunity $growthOpportunity): RedirectResponse
    {
        MarketingTask::firstOrCreate(
            [
                'website_id' => $growthOpportunity->website_id,
                'growth_opportunity_id' => $growthOpportunity->id,
            ],
            [
                'title' => $this->taskTitle($growthOpportunity),
                'description' => $growthOpportunity->recommendation,
                'expected_result' => $growthOpportunity->expected_result,
                'priority' => $growthOpportunity->priority,
                'source_type' => $growthOpportunity->source_type,
                'source_value' => $growthOpportunity->source_value,
                'related_page_url' => $growthOpportunity->related_page_url,
                'status' => 'pending',
            ]
        );

        $growthOpportunity->update(['status' => 'in_progress']);

        return back()->with('success', 'Task created from growth opportunity.');
    }

    public function update(Request $request, GrowthOpportunity $growthOpportunity): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:open,reviewed,in_progress,completed,ignored'],
        ]);

        $growthOpportunity->update($data);

        return back()->with('success', 'Growth opportunity updated.');
    }

    private function taskTitle(GrowthOpportunity $opportunity): string
    {
        return match ($opportunity->opportunity_type) {
            'increase_ctr', 'increase_ctr_and_conversion' => 'Rewrite title/meta for '.$this->shortSource($opportunity),
            'mobile_conversion' => 'Add sticky booking CTA on mobile',
            'improve_position' => 'Improve internal links and content for '.$this->shortSource($opportunity),
            'improve_booking_cta' => 'Improve booking CTA on '.$this->shortSource($opportunity),
            default => 'Improve growth opportunity: '.$this->shortSource($opportunity),
        };
    }

    private function shortSource(GrowthOpportunity $opportunity): string
    {
        return $opportunity->related_page_url ?: $opportunity->source_value;
    }
}
