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
namespace Hyperf\XxlJob\Requests;

use JetBrains\PhpStorm\Pure;

class BaseRequest
{
    #[Pure]
    public static function create(array $data = [])
    {
        $obj = new static();
        foreach ($data as $k => $v) {
            //kuke微服务会在入参的时候对驼峰的参数自动转为下划线
            $k = self::camel($k);
            if (property_exists($obj, $k)) {
                $obj->{$k} = $v;
            }
        }
        return $obj;
    }

    /**
     *下划线转为驼峰
     * @param int|string $value
     * @return int|string
     */
    public static function camel($value)
    {
        if (!is_string($value)) {
            return $value;
        }
        $value = ucwords(str_replace(['_'], ' ', $value));
        $value = str_replace(' ', '', $value);
        return lcfirst($value);
    }
}
