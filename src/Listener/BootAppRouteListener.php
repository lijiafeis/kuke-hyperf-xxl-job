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

use Exception;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BootApplication;
use Hyperf\HttpServer\Router\DispatcherFactory;
use Hyperf\Server\ServerInterface;
use Hyperf\XxlJob\Annotation\JobHandler;
use Hyperf\XxlJob\Annotation\XxlJob;
use Hyperf\XxlJob\Application;
use Hyperf\XxlJob\Dispatcher\XxlJobRoute;
use Hyperf\XxlJob\JobDefinition;
use Hyperf\XxlJob\Logger\XxlJobHelper;
use Psr\Container\ContainerInterface;

class BootAppRouteListener implements ListenerInterface
{
    /**
     * @var Application
     */
    private $application;

    /**
     * @var ContainerInterface
     */
    private $container;

    protected ConfigInterface $config;

    protected StdoutLoggerInterface $logger;

    public function __construct(ContainerInterface $container, Application $application)
    {
        $this->container = $container;
        $this->application = $application;
        $this->config = $container->get(ConfigInterface::class);
        $this->logger = $container->get(StdoutLoggerInterface::class);
    }

    public function listen(): array
    {
        return [
            BootApplication::class,
        ];
    }

    public function process(object $event)
    {
        if (! $this->config->get('xxl_job.enable', false)) {
            return;
        }
        $prefixUrl = $this->config->get('xxl_job.prefix_url', 'php-xxl-job');
        $servers = $this->config->get('server.servers');
        $httpServerRouter = null;
        $serverConfig = null;
        foreach ($servers as $server) {
            $router = $this->container->get(DispatcherFactory::class)->getRouter($server['name']);
            if (empty($httpServerRouter) && $server['type'] == ServerInterface::SERVER_HTTP) {
                $httpServerRouter = $router;
                $serverConfig = $server;
            }
        }
        if (empty($httpServerRouter)) {
            $this->logger->warning('XxlJob: HTTP Service is not ready.');
            $this->application->getConfig()->setEnable(false);
            return;
        }
        $this->initAnnotationRoute();

        $route = new XxlJobRoute();
        if (! empty($prefixUrl)) {
            $prefixUrl = trim($prefixUrl, '/') . '/';
        } else {
            $prefixUrl = '';
        }
        $route->add($httpServerRouter, $prefixUrl);

        $host = $serverConfig['host'];
        if (in_array($host, ['0.0.0.0', 'localhost'])) {
            $host = $this->getIp();
        }

        $url = sprintf('http://%s:%s/%s', $host, $serverConfig['port'], $prefixUrl);
        $this->application->getConfig()->setClientUrl($url);
    }

    private function initAnnotationRoute(): void
    {
        $methods = AnnotationCollector::getMethodsByAnnotation(XxlJob::class);
        foreach ($methods as $method) {
            $annotation = $method['annotation'];
            if ($annotation instanceof XxlJob) {
                $this->application->registerJobHandler($annotation->value, new JobDefinition($method['class'], $method['method'], $annotation->init, $annotation->destroy));
            }
        }
    }

    /**
     * @throws Exception
     */
    private function getIp(): string
    {
        $ips = swoole_get_local_ip();
        if (is_array($ips) && ! empty($ips)) {
            return current($ips);
        }
        /** @var mixed|string $ip */
        $ip = gethostbyname(gethostname());
        if (is_string($ip)) {
            return $ip;
        }
        throw new Exception('Can not get the internal IP.');
    }
}
