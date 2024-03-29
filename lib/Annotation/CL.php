<?php

namespace Fengdangxing\CurrentLimit\Annotation;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
#[Attribute(Attribute::TARGET_METHOD)]
class CL extends AbstractAnnotation
{
    public $uniqueName;//唯一值
    public $currentable = true;//并发限制开启
    public $limitable = true;//限流开启

    public function __construct(...$value)
    {
        parent::__construct(...$value);
    }
}
