<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace Hyperf\XxlJob\Listener;

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\OnShutdown;
use Hyperf\Server\Event\CoroutineServerStop;
use Hyperf\XxlJob\ApiRequest;
use Hyperf\XxlJob\Config;
use Psr\Container\ContainerInterface;

class OnShutdownListener implements ListenerInterface
{
    protected ContainerInterface $container;

    protected StdoutLoggerInterface $logger;

    protected bool $processed = false;

    protected Config $xxlConfig;

    protected ApiRequest $apiRequest;

    public function __construct(ContainerInterface $container, ApiRequest $apiRequest)
    {
        $this->container = $container;
        $this->logger = $container->get(StdoutLoggerInterface::class);
        $this->xxlConfig = $container->get(Config::class);
        $this->apiRequest = $apiRequest;
    }

    public function listen(): array
    {
        return [
            OnShutdown::class,
            CoroutineServerStop::class,
        ];
    }

    public function process(object $event)
    {
        if ($this->processed) {
            return;
        }
        $this->processed = true;

        if (! $this->xxlConfig->isEnable()) {
            return;
        }
        $response = $this->apiRequest->registryRemove($this->xxlConfig->getAppName(), $this->xxlConfig->getClientUrl());
        if ($response->getStatusCode() === 200) {
            $this->logger->info(sprintf('Remove the XXL-JOB app name: %s url:%s is successful', $this->xxlConfig->getAppName(), $this->xxlConfig->getClientUrl()));
        } else {
            $this->logger->error(sprintf('Failed to remove the XXL-JOB app name:%s url:%s', $this->xxlConfig->getAppName(), $this->xxlConfig->getClientUrl()));
        }
    }
}
