<?php

namespace App\Services\Agents;

use App\Models\Agent;
use App\Models\AgentMemory;
use App\Models\Website;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class AgentMemoryService
{
    public const TYPES = ['workspace_context', 'previous_decision', 'approved_action', 'ignored_action', 'completed_task', 'successful_action', 'unsuccessful_action', 'inconclusive_action', 'performance_pattern', 'conversion_goal_context', 'user_instruction'];

    public function remember(Agent $agent, ?Website $website, string $type, string $key, string $value, array $attributes = []): AgentMemory
    {
        $this->guard($type, $key, $value);

        return AgentMemory::firstOrCreate(
            ['agent_id' => $agent->id, 'website_id' => $website?->id, 'memory_type' => $type, 'memory_key' => $key],
            ['memory_value' => $value, ...$this->attributes($attributes)]
        );
    }

    public function updateOrRemember(Agent $agent, ?Website $website, string $type, string $key, string $value, array $attributes = []): AgentMemory
    {
        $this->guard($type, $key, $value);

        return AgentMemory::updateOrCreate(
            ['agent_id' => $agent->id, 'website_id' => $website?->id, 'memory_type' => $type, 'memory_key' => $key],
            ['memory_value' => $value, ...$this->attributes($attributes)]
        );
    }

    public function recall(Agent $agent, ?Website $website, ?string $key = null): Collection
    {
        return $this->activeQuery($agent, $website)->when($key, fn (Builder $query) => $query->where('memory_key', $key))->latest()->get();
    }

    public function recallByType(Agent $agent, ?Website $website, string $type): Collection
    {
        $this->assertType($type);

        return $this->activeQuery($agent, $website)->where('memory_type', $type)->latest()->get();
    }

    public function forget(AgentMemory $memory): bool
    {
        return (bool) $memory->delete();
    }

    public function expireOldMemories(): int
    {
        return AgentMemory::whereNotNull('expires_at')->where('expires_at', '<=', now())->count();
    }

    public function buildAgentMemoryContext(Agent $agent, Website $website): array
    {
        $memories = $this->activeQuery($agent, $website)->latest()->limit(20)->get();

        return [
            'conversion_goal' => $website->primary_conversion_goal,
            'approved_actions' => $this->values($memories, 'approved_action'),
            'ignored_recommendations' => $this->values($memories, 'ignored_action'),
            'completed_work' => $this->values($memories, 'completed_task'),
            'workspace_preferences' => $memories->whereIn('memory_type', ['workspace_context', 'conversion_goal_context', 'user_instruction'])->pluck('memory_value')->values()->all(),
            'previous_decisions' => $this->values($memories, 'previous_decision'),
            'performance_patterns' => $this->values($memories, 'performance_pattern'),
            'memory_ids' => $memories->pluck('id')->all(),
        ];
    }

    private function activeQuery(Agent $agent, ?Website $website): Builder
    {
        return AgentMemory::where('agent_id', $agent->id)
            ->where(fn (Builder $query) => $query->whereNull('website_id')->when($website, fn (Builder $query) => $query->orWhere('website_id', $website->id)))
            ->where(fn (Builder $query) => $query->whereNull('valid_from')->orWhere('valid_from', '<=', now()))
            ->where('enabled', true)
            ->where(fn (Builder $query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    private function values(Collection $memories, string $type): array
    {
        return $memories->where('memory_type', $type)->pluck('memory_value')->values()->all();
    }

    private function guard(string $type, string $key, string $value): void
    {
        $this->assertType($type);
        $subject = strtolower($key.' '.$value);
        if (preg_match('/openai.{0,12}key|google.{0,12}token|access[_ -]?token|refresh[_ -]?token|authorization\s*:|bearer\s+|password|saved credentials|complete (customer|visitor) record/', $subject)) {
            throw new InvalidArgumentException('Sensitive credentials or complete customer/visitor records cannot be stored as agent memory.');
        }
    }

    private function assertType(string $type): void
    {
        if (! in_array($type, self::TYPES, true)) {
            throw new InvalidArgumentException('Unsupported agent memory type.');
        }
    }

    private function attributes(array $attributes): array
    {
        return collect($attributes)->only(['confidence', 'enabled', 'source_type', 'source_id', 'learning_metadata', 'review_notes', 'last_used_at', 'valid_from', 'expires_at'])->all();
    }
}
