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
namespace Hyperf\XxlJob\Handler;

use Hyperf\XxlJob\Requests\RunRequest;

interface JobHandlerInterface
{
    public function init(RunRequest $request): void;
    public function execute(RunRequest $request): void;
    public function destroy(RunRequest $request): void;
}
