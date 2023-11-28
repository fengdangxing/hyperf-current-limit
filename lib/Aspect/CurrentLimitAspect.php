<?php

namespace Fengdangxing\CurrentLimit\Aspect;

use Fengdangxing\CurrentLimit\Annotation\CL;
use Fengdangxing\CurrentLimit\CurrentLimit;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Codec\Json;

/**
 * @Aspect
 */
class CurrentLimitAspect extends AbstractAspect
{
    public $classes = [];

    public $annotations = [
        CL::class,
    ];

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $list = AnnotationCollector::getMethodsByAnnotation($this->annotations[0]);
        $request = ApplicationContext::getContainer()->get(\Hyperf\HttpServer\Contract\RequestInterface::class);
        $currentLimitClass = ApplicationContext::getContainer()->get(CurrentLimit::class);
        $url = $request->path();
        $params = $request->all();

        foreach ($list as $key => $anno) {
            if ($proceedingJoinPoint->className == $anno['class'] && $proceedingJoinPoint->methodName == $anno['method']) {
                $token = $this->getUniqueName($anno['annotation']->uniqueName, $request);
                if ($anno['annotation']->limitable) {
                    $currentLimitClass->isActionAllowed($token, $url);
                }
                if ($anno['annotation']->currentable) {
                    $currentLimitClass->isConcurrentRequests($params, $url, $token);
                }
                try {
                    $result = $proceedingJoinPoint->process();
                    if ($anno['annotation']->currentable) {
                        $currentLimitClass->delConcurrentRequests($params, $url, $token);
                    }
                } catch (\Exception $exception) {
                    if ($anno['annotation']->currentable) {
                        $currentLimitClass->delConcurrentRequests($params, $url, $token);
                    }
                    throw $exception;
                }

            }
        }
        return $result;
    }

    private function getUniqueName($uniqueName, $request)
    {
        $token = '';
        $uniqueNameArray = explode('|', $uniqueName);
        foreach ($uniqueNameArray as $value) {
            $token .= $request->header($value);
        }
        return md5($token);
    }
}