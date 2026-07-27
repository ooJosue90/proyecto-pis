<?php

declare(strict_types=1);

return [
    'api_key' => env_value('GEMINI_API_KEY', ''),
    'model' => env_value('GEMINI_MODEL', 'gemini-2.0-flash'),
    'timeout' => 25,
    'max_context_chars' => 12000,
    'max_rows_per_topic' => 20,
];
