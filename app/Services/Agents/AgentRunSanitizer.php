<?php

namespace App\Services\Agents;

class AgentRunSanitizer
{
    public function text(?string $value): ?string
    {
        if ($value === null) return null;
        $value = preg_replace('/(Bearer\s+)[^\s]+/i', '$1[redacted]', $value) ?? '[redacted]';
        $value = preg_replace('/(api[_-]?key|access[_-]?token|refresh[_-]?token|authorization|password|secret|client[_-]?secret)\s*[:=]\s*[^\s,;\}\]]+/i', '$1=[redacted]', $value) ?? '[redacted]';
        return preg_replace('/("(?:api_key|access_token|refresh_token|authorization|password|secret|client_secret)"\s*:\s*")[^"]+/i', '$1[redacted]', $value) ?? '[redacted]';
    }
}
