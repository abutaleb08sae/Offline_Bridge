<?php

namespace App\Services;

use App\Models\JudgeProblem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Throwable;

class LocalJudgeRunner
{
    private const VERDICTS = [
        'Accepted',
        'Wrong Answer',
        'Compilation Error',
        'Time Limit Exceeded',
        'Security Violation',
        'Runtime Error',
        'Output Limit Exceeded',
    ];

    /**
     * Send a submission to a trusted helper that must execute it in a disposable Windows VM.
     *
     * @return array{verdict: string, details: ?string}
     */
    public function judge(
        string $language,
        JudgeProblem $problem,
        Collection $testCases,
        string $code
    ): array {
        $runnerPath = config('judge.vm_runner');

        if (!is_string($runnerPath) || $runnerPath === '') {
            return [
                'verdict' => 'Runner Unavailable',
                'details' => 'No isolated Windows VM runner is configured. The submission was saved but not executed.',
            ];
        }

        if (!str_starts_with($runnerPath, '\\\\') && preg_match('/^[A-Za-z]:\\\\/', $runnerPath) !== 1) {
            return [
                'verdict' => 'Runner Unavailable',
                'details' => 'The configured VM runner path must be an absolute Windows path.',
            ];
        }

        if (!is_file($runnerPath)) {
            return [
                'verdict' => 'Runner Unavailable',
                'details' => 'The configured Windows VM runner executable was not found.',
            ];
        }

        if ($testCases->count() > (int) config('judge.max_test_cases', 100)) {
            return [
                'verdict' => 'Runner Error',
                'details' => 'The problem exceeds the configured maximum number of test cases.',
            ];
        }

        if ($testCases->isEmpty()) {
            return [
                'verdict' => 'Runner Error',
                'details' => 'No test cases are configured for this problem.',
            ];
        }

        $timeLimit = max(1, min((int) $problem->time_limit, (int) config('judge.max_time_limit', 10)));
        $memoryLimit = max(64, min((int) $problem->memory_limit, (int) config('judge.max_memory_limit', 512)));
        $request = [
            'protocol_version' => 1,
            'language' => $language,
            'code' => $code,
            'time_limit' => $timeLimit,
            'memory_limit' => $memoryLimit,
            'test_cases' => $testCases->map(static fn ($testCase): array => [
                'input' => $testCase->input,
                'expected_output' => $testCase->expected_output,
            ])->values()->all(),
        ];

        try {
            $payload = json_encode($request, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            Log::error('Unable to encode local judge request.', ['exception' => $exception]);

            return ['verdict' => 'Runner Error', 'details' => 'The submission could not be prepared for the VM runner.'];
        }

        if (strlen($payload) > (int) config('judge.max_request_bytes', 2097152)) {
            return [
                'verdict' => 'Runner Error',
                'details' => 'The submission and test cases exceed the VM runner request limit.',
            ];
        }

        $process = new Process([$runnerPath, '--json-stdin']);
        $process->setInput($payload);
        $process->setTimeout((float) config('judge.runner_timeout', 180));
        $outputLimit = (int) config('judge.max_output_bytes', 1048576);
        $stdout = '';
        $stderr = '';
        $outputExceeded = false;

        try {
            $process->start();

            while ($process->isRunning()) {
                $stdout .= $process->getIncrementalOutput();
                $stderr .= $process->getIncrementalErrorOutput();
                if (strlen($stdout) + strlen($stderr) > $outputLimit) {
                    $outputExceeded = true;
                }
                $process->clearOutput();
                $process->clearErrorOutput();

                if (strlen($stdout) + strlen($stderr) > $outputLimit) {
                    $outputExceeded = true;
                    $process->stop(0);
                    break;
                }

                usleep(10000);
            }

            $stdout .= $process->getIncrementalOutput();
            $stderr .= $process->getIncrementalErrorOutput();
        } catch (ProcessTimedOutException) {
            $process->stop(0);

            return ['verdict' => 'Runner Error', 'details' => 'The isolated VM runner did not respond before its timeout.'];
        } catch (Throwable $exception) {
            Log::error('Unable to invoke the configured local judge VM runner.', ['exception' => $exception]);

            return ['verdict' => 'Runner Error', 'details' => 'The configured isolated VM runner could not be started.'];
        }

        if ($outputExceeded) {
            return ['verdict' => 'Runner Error', 'details' => 'The VM runner response exceeded the configured output limit.'];
        }

        if (!$process->isSuccessful()) {
            Log::error('Local judge VM runner returned a failure.', [
                'exit_code' => $process->getExitCode(),
                'stderr' => mb_substr($stderr, 0, 8000),
            ]);

            return ['verdict' => 'Runner Error', 'details' => 'The isolated VM runner failed to process the submission.'];
        }

        try {
            $response = json_decode($stdout, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            Log::error('Local judge VM runner returned invalid JSON.', ['exception' => $exception]);

            return ['verdict' => 'Runner Error', 'details' => 'The isolated VM runner returned an invalid response.'];
        }

        if (
            !is_array($response)
            || ($response['protocol_version'] ?? null) !== 1
            || !is_string($response['verdict'] ?? null)
            || !in_array($response['verdict'], self::VERDICTS, true)
        ) {
            Log::error('Local judge VM runner returned an unsupported response.');

            return ['verdict' => 'Runner Error', 'details' => 'The isolated VM runner returned an unsupported response.'];
        }

        return [
            'verdict' => $response['verdict'],
            'details' => isset($response['details']) && is_string($response['details'])
                ? mb_substr($response['details'], 0, 8000)
                : null,
        ];
    }
}
