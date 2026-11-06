<?php

return [
    'docker_binary' => env('JUDGE_DOCKER_BINARY', 'docker'),
    'docker_image' => env('JUDGE_DOCKER_IMAGE', 'offline-bridge/judge:latest'),
    'compile_timeout' => 30,
    'max_code_bytes' => 65536,
    'max_output_bytes' => 1048576,
    'max_time_limit' => 10,
    'max_memory_limit' => 512,
];
