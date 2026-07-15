<?php

return [
    'clicks_change_percent' => (float) env('AGENT_CLICKS_CHANGE_PERCENT', 20),
    'impressions_change_percent' => (float) env('AGENT_IMPRESSIONS_CHANGE_PERCENT', 20),
    'ctr_change_points' => (float) env('AGENT_CTR_CHANGE_POINTS', 0.5),
    'position_change' => (float) env('AGENT_POSITION_CHANGE', 2),
    'minimum_impressions' => (int) env('AGENT_MINIMUM_IMPRESSIONS', 30),
    'minimum_clicks' => (int) env('AGENT_MINIMUM_CLICKS', 5),
    'new_query_impressions' => (int) env('AGENT_NEW_QUERY_IMPRESSIONS', 30),
    'scheduled_interval_minutes' => (int) env('AGENT_SCHEDULE_INTERVAL_MINUTES', 10),
];
