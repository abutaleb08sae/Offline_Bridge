<?php

return [
    'vm_runner' => env('JUDGE_VM_RUNNER'),
    'runner_timeout' => 180,
    'max_code_bytes' => 65536,
    'max_output_bytes' => 1048576,
    'max_request_bytes' => 2097152,
    'max_test_cases' => 100,
    'max_time_limit' => 10,
    'max_memory_limit' => 512,
];
