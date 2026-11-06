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
     * Judge the submission using either configured VM runner or fallback native execution.
     *
     * @return array{verdict: string, details: ?string}
     */
    public function judge(
        string $language,
        JudgeProblem $problem,
        Collection $testCases,
        string $code
    ): array {
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

        $runnerPath = config('judge.vm_runner');

        $hasValidVmRunner = is_string($runnerPath)
            && $runnerPath !== ''
            && (str_starts_with($runnerPath, '\\\\') || preg_match('/^[A-Za-z]:\\\\/', $runnerPath) === 1)
            && is_file($runnerPath);

        if ($hasValidVmRunner) {
            return $this->executeWithVmRunner($runnerPath, $language, $problem, $testCases, $code);
        }

        return $this->executeNatively($language, $problem, $testCases, $code);
    }

    /**
     * Native Windows execution fallback logic for Master PC.
     */
    private function executeNatively(
        string $language,
        JudgeProblem $problem,
        Collection $testCases,
        string $code
    ): array {
        $tempDir = storage_path('app/temp_judge');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $tempDirReal = realpath($tempDir) ?: $tempDir;
        $uniqueId = 'sub_' . time() . '_' . rand(1000, 9999);
        $lang = strtolower($language);
        
        $sourcePath = $tempDirReal . DIRECTORY_SEPARATOR . $uniqueId . ($lang === 'cpp' ? '.cpp' : '.py');
        $exePath = $tempDirReal . DIRECTORY_SEPARATOR . $uniqueId . '.exe';

        file_put_contents($sourcePath, $code);

        // Override environment variables to force g++ and process runners to use local temp directory
        $envVars = [
            'TMP' => $tempDirReal,
            'TEMP' => $tempDirReal,
            'TMPDIR' => $tempDirReal,
            'SYSTEMROOT' => getenv('SystemRoot') ?: 'C:\\Windows',
            'PATH' => getenv('PATH'),
        ];

        try {
            // 1. C++ Compilation Stage
            if ($lang === 'cpp') {
                $compileProcess = new Process(['g++', $sourcePath, '-o', $exePath], $tempDirReal, $envVars);
                $compileProcess->run();

                if (!$compileProcess->isSuccessful()) {
                    return [
                        'verdict' => 'Compilation Error',
                        'details' => $compileProcess->getErrorOutput() ?: $compileProcess->getOutput(),
                    ];
                }
            }

            $timeLimit = max(1, (int) $problem->time_limit);

            // 2. Test Execution Loop
            foreach ($testCases as $index => $testCase) {
                if ($lang === 'cpp') {
                    $process = new Process([$exePath], $tempDirReal, $envVars);
                } else {
                    $process = new Process(['python', $sourcePath], $tempDirReal, $envVars);
                }

                $process->setInput((string) ($testCase->input ?? ''));
                $process->setTimeout((float) $timeLimit);

                try {
                    $process->run();

                    if (!$process->isSuccessful()) {
                        $errOut = trim($process->getErrorOutput());
                        $stdOut = trim($process->getOutput());
                        $exitCode = $process->getExitCode();

                        return [
                            'verdict' => 'Runtime Error',
                            'details' => "Exit Code: {$exitCode}\nLog: " . ($errOut ?: ($stdOut ?: 'Unknown execution crash.')),
                        ];
                    }

                    $actualOutput = trim(str_replace("\r\n", "\n", $process->getOutput()));
                    $expectedOutput = trim(str_replace("\r\n", "\n", (string) ($testCase->expected_output ?? '')));

                    if ($actualOutput !== $expectedOutput) {
                        return [
                            'verdict' => 'Wrong Answer',
                            'details' => "Failed on Test Case #" . ($index + 1) . "\nExpected:\n{$expectedOutput}\n\nGot:\n{$actualOutput}",
                        ];
                    }
                } catch (ProcessTimedOutException) {
                    return [
                        'verdict' => 'Time Limit Exceeded',
                        'details' => "Execution time exceeded the limit of {$timeLimit} second(s).",
                    ];
                } catch (Throwable $e) {
                    return [
                        'verdict' => 'Runtime Error',
                        'details' => 'Execution Exception: ' . $e->getMessage(),
                    ];
                }
            }

            return [
                'verdict' => 'Accepted',
                'details' => 'All test cases passed successfully.',
            ];
        } finally {
            // Cleanup Temporary Files
            if (file_exists($sourcePath)) {
                @unlink($sourcePath);
            }
            if (file_exists($exePath)) {
                @unlink($exePath);
            }
        }
    }

    /**
     * External VM Runner Handler.
     */
    private function executeWithVmRunner(
        string $runnerPath,
        string $language,
        JudgeProblem $problem,
        Collection $testCases,
        string $code
    ): array {
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
                    $process->stop(0);
                    break;
                }
                $process->clearOutput();
                $process->clearErrorOutput();
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