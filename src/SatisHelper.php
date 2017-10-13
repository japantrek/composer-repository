<?php

namespace nvbooster\Repo;

use Symfony\Component\Process\Process;

class SatisHelper
{
    /**
     * @var array
     */
    protected $binaryPath;

    /**
     * @var array
     */
    protected $configPath;

    /**
     * @var array
     */
    protected $webPath;

    /**
     * @var array
     */
    protected $logsPath;

    /**
     * @var array
     */
    protected $reposIndex = false;

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $base = dirname(__DIR__);
        $this->binaryPath = realpath($base.'/bin/satis');

        if (!file_exists($base.'/app/config/repos.json')) {
            throw new \Exception('Satis configuration not found');
        }
        $this->configPath = realpath($base.'/app/config/repos.json');
        $this->webPath = realpath($base.'/web');
        $this->logsPath = realpath($base.'/var/logs');
    }

    /**
     * @throws \Exception
     *
     * @return array
     */
    public function getReposIndex()
    {
        if (false === $this->reposIndex) {
            if (null == $json = json_decode(file_get_contents($this->configPath), true)) {
                throw new \Exception('Satis configuration file is mailformed');
            }

            $this->reposIndex = $json['repositories'];
        }

        return $this->reposIndex;
    }

    /**
     * @param array $context (optional)
     * @param string $repository (optional)
     *
     * @return number
     */
    public function runSatis($context, $repository = null)
    {
        $context = array_merge(
            array('channel' => 'default', 'repository' => 'n/a', 'revision' => 'n/a'),
            $context
        );

        $logFile = fopen($this->logsPath.'/webhooks_log', 'a');
        fwrite($logFile, sprintf(
            "%s: [%s] running webhook for %s at %s\n",
            date('c'),
            $context['channel'],
            $context['repository'],
            $context['revision']
        ));
        fclose($logFile);

        $command = sprintf('%s build %s %s', $this->binaryPath, $this->configPath, $this->webPath);

        if ($repository) {
            $command .= ' --repository-url '.$repository;
        }
        $process = new Process($command);
        $exitCode = $process->run(function ($type, $buffer) {
            if ('err' === $type) {
                echo 'E';
                error_log($buffer);
            } else {
                echo '.';
            }
        });

        $logFile = fopen($this->logsPath.'/webhooks_log', 'a');
        fwrite($logFile, sprintf(
            "%s: [%s] webhook for %s at %s: satis index %s\n",
            date('c'),
            $context['channel'],
            $context['repository'],
            $context['revision'],
            $exitCode ? 'rebuild failed' : 'rebuilt successfully'
        ));
        fclose($logFile);

        return $exitCode;
    }
}