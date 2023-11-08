####简版使用-主要增加自定义方法 放在验证类处理
[validation](https://github.com/hyperf/validation)
___________

```php
#配置路径 config/annotations.php 没有改文件新建
return [
    'scan' => [
        'paths' => [
            BASE_PATH . '/app',
            BASE_PATH . '/vendor/fengdangxing',//增加该配置
        ],
        'ignore_annotations' => [
            'mixin',
            'Notes',
            'Author',
            'Data',
            'Date'
        ],
    ],
];
```

#配置config/app.php
```php
return [
    'fengdangxing' => [
        'currentLimit' => [
            'rateLimitMin' => 50,//限流时间间隔内下限次数
            'rateLimitMax' => 100,//限流时间间隔内上限次数
            'rateLimitPeriod' => 5,//限流时间间隔s
            'rateLimitPausePeriod' => 5,//限流暂停时间s
            'rateLimitForbidPeriod' => 2 * 3600,//封禁时间s
        ]
    ]
];

```
#控制器 userName 获取header参数名称-用于限制用户 -- 目前只能限制都用户级别或者有每个请求唯一的参数
```php
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

namespace App\Controller;

use Fengdangxing\CurrentLimit\Annotation\CL;

class IndexController extends AbstractController
{
    /**
     * @CL(uniqueName="token|member_token|user_id",currentable=true,limitable=true)
     */
    public function index()
    {
        $user = $this->request->input('user', 'Hyperf');
        $method = $this->request->getMethod();
        $list = PublishBatchModel::getAllList();
        return [
            'method' => $method,
            'message' => "Hello {$user}.",
            $list
        ];
    }
}
#uniqueName 唯一值；头部信息
```
