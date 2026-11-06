<?php

namespace App\Services;

use App\Models\JudgeProblem;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;
use Symfony\Component\Process\Process;

class LocalJudgeRunner
{
    /**
     * Judge every case inside one resource-limited, network-disabled container.
     *
     * @return array{verdict: string, details: ?string}
     */
    public function judge(
        string $language,
        JudgeProblem $problem,
        Collection $testCases,
        string $workDirectory
    ): array {
        $containerName = 'offline-bridge-judge-' . bin2hex(random_bytes(12));
        $timeLimit = max(1, min((int) $problem->time_limit, (int) config('judge.max_time_limit', 10)));
        $memoryLimit = max(64, min((int) $problem->memory_limit, (int) config('judge.max_memory_limit', 512)));
        $compileTimeout = (int) config('judge.compile_timeout', 30);

        try {
            $start = $this->runProcess(new Process([
                (string) config('judge.docker_binary', 'docker'),
                'run',
                '--detach',
                '--name', $containerName,
                '--network', 'none',
                '--read-only',
                '--cap-drop', 'ALL',
                '--security-opt', 'no-new-privileges',
                '--pids-limit', '64',
                '--memory', $memoryLimit . 'm',
                '--memory-swap', $memoryLimit . 'm',
                '--cpus', '1',
                '--user', '65534:65534',
                '--tmpfs', '/tmp:rw,nosuid,nodev,size=64m',
                '--mount', 'type=bind,source=' . $workDirectory . ',target=/input,readonly',
                (string) config('judge.docker_image', 'offline-bridge/judge:latest'),
                'sleep',
                'infinity',
            ]), 15);

            if (!$start['successful']) {
                throw new RuntimeException('Unable to start the isolated judge container: ' . $this->limitDetails($start['stdout'] . $start['stderr']));
            }

            if ($language === 'cpp') {
                $compile = $this->runProcess(new Process([
                    (string) config('judge.docker_binary', 'docker'),
                    'exec',
                    $containerName,
                    'timeout',
                    '-k', '1',
                    $compileTimeout . 's',
                    'g++',
                    '-std=c++17',
                    '-O2',
                    '/input/main.cpp',
                    '-o',
                    '/tmp/solution',
                ]), $compileTimeout + 5);
                $this->throwIfDockerUnavailable($compile['stderr']);

                if ($compile['output_limit']) {
                    return ['verdict' => 'Compilation Error', 'details' => 'Compiler output exceeded the judge output limit.'];
                }

                if ($compile['timed_out']) {
                    return ['verdict' => 'Compilation Error', 'details' => 'Compilation exceeded the allowed compile time.'];
                }

                if (!$compile['successful']) {
                    return ['verdict' => 'Compilation Error', 'details' => $this->limitDetails($compile['stdout'] . $compile['stderr'])];
                }
            }

            foreach ($testCases as $index => $testCase) {
                $command = $language === 'cpp'
                    ? ['timeout', '-k', '1', $timeLimit . 's', '/tmp/solution']
                    : ['timeout', '-k', '1', $timeLimit . 's', 'python3', '/input/main.py'];
                $process = new Process([
                    (string) config('judge.docker_binary', 'docker'),
                    'exec',
                    '--interactive',
                    $containerName,
                    ...$command,
                ]);
                $process->setInput($testCase->input);

                $execution = $this->runProcess($process, $timeLimit + 5);
                $this->throwIfDockerUnavailable($execution['stderr']);

                if ($execution['timed_out'] || in_array($execution['exit_code'], [124, 137], true)) {
                    return ['verdict' => 'Time Limit Exceeded', 'details' => 'Execution exceeded the problem time limit.'];
                }

                if ($execution['output_limit']) {
                    return ['verdict' => 'Output Limit Exceeded', 'details' => 'Output exceeded the judge output limit.'];
                }

                if (!$execution['successful']) {
                    $details = $this->limitDetails($execution['stderr']);

                    return [
                        'verdict' => 'Runtime Error',
                        'details' => $details !== '' ? $details : 'The program exited with a non-zero status.',
                    ];
                }

                if (trim($execution['stdout']) !== trim($testCase->expected_output)) {
                    return [
                        'verdict' => 'Wrong Answer',
                        'details' => 'Output did not match on test case ' . ($index + 1) . '.',
                    ];
                }
            }

            return ['verdict' => 'Accepted', 'details' => null];
        } finally {
            $this->removeContainer($containerName);
        }
    }

    /**
     * @return array{stdout: string, stderr: string, successful: bool, exit_code: ?int, timed_out: bool, output_limit: bool}
     */
    private function runProcess(Process $process, float $timeout): array
    {
        $process->setTimeout($timeout);
        $stdout = '';
        $stderr = '';
        $timedOut = false;
        $outputLimitExceeded = false;

        try {
            $process->start();

            while ($process->isRunning()) {
                $stdout .= $process->getIncrementalOutput();
                $stderr .= $process->getIncrementalErrorOutput();
                $process->clearOutput();
                $process->clearErrorOutput();

                if (strlen($stdout) + strlen($stderr) > (int) config('judge.max_output_bytes', 1048576)) {
                    $outputLimitExceeded = true;
                    $process->stop(0);
                    break;
                }

                usleep(10000);
            }

            $stdout .= $process->getIncrementalOutput();
            $stderr .= $process->getIncrementalErrorOutput();
        } catch (\Symfony\Component\Process\Exception\ProcessTimedOutException) {
            $timedOut = true;
            $process->stop(0);
        }

        return [
            'stdout' => $stdout,
            'stderr' => $stderr,
            'successful' => $process->isSuccessful(),
            'exit_code' => $process->getExitCode(),
            'timed_out' => $timedOut,
            'output_limit' => $outputLimitExceeded,
        ];
    }

    private function throwIfDockerUnavailable(string $errorOutput): void
    {
        $errorOutput = strtolower($errorOutput);
        foreach ([
            'error response from daemon',
            'cannot connect to the docker daemon',
            'error during connect',
            'no such container',
            'unable to find image',
            'pull access denied',
            'the system cannot find the file specified',
            'docker desktop is unable to start',
        ] as $message) {
            if (str_contains($errorOutput, $message)) {
                throw new RuntimeException('Docker judge runner error: ' . $this->limitDetails($errorOutput));
            }
        }
    }

    private function removeContainer(string $containerName): void
    {
        $process = new Process([
            (string) config('judge.docker_binary', 'docker'),
            'rm',
            '--force',
            $containerName,
        ]);
        $process->setTimeout(10);
        $process->run();

        if (!$process->isSuccessful() && !str_contains($process->getErrorOutput(), 'No such container')) {
            throw new RuntimeException('Unable to clean up judge container: ' . $this->limitDetails($process->getErrorOutput()));
        }
    }

    private function limitDetails(string $details): string
    {
        return mb_substr(trim($details), 0, 8000);
    }
}
