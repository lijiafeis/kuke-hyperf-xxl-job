[toc]



## hyperf_xxl_job

此为 xxl-job 的 PHP 版本的任务执行器(Job Executor)，特别适配于 Hyperf 框架，其余框架尚未验证适配性

此版本根据 [hyperf/xxl-job-incubator](https://github.com/hyperf/xxl-job-incubator) 改造而来

### 优点

- 分布式任务调度平台
- 任务可以随时关闭与开启
- 日志可通过服务端查看


## 使用须知

> 1. xxl-job 服务端版本需 >= 2.2.0
> 2. 无法取消正在执行的任务

### Bean 模式(类形式)

Bean 模式任务，支持基于类的开发方式，每个任务对应一个 PHP 类

优点：与 Hyperf 整合性好，易于管理   
缺点：任务运行于单独的，协程任务代码不能存在阻塞 IO，每个 Job 需占用一个类文件，Job 逻辑简单但数量过多时过于累赘

### Glue 脚本模式

该模式下，可支持任务以将源码方式维护在调度中心，支持通过 XXL-JOB 提供的 Web IDE 在线编写代码和在线更新，因此不需要指定固定的 `JobHandler`   
脚本模式支持多种脚本语言编写 Job 代码，包括 PHP、Python、NodeJs、Shell、PowerShell，在 XXL-JOB 新建任务时选择对应的模式即可，例如 `GLUE(PHP)` 即代表 PHP 语言的脚本模式，所有脚本模式的任务会以一个独立的进程来运行，故在 PHP 下也可支持编写存在 IO 阻塞的代码

> 要使用 `Glue 脚本模式` 必须配置 Access Token 方可启用

优点：极度灵活，可以实现不重启新增和修改 Job 代码，支持多种脚本语言，独立进程   
缺点：大批量任务时容易造成进程数过多，脚本代码由 XXL-JOB 远程编辑发放容易导致安全问题，Job 代码可对 Executor 所在服务器环境进行与启动 Hyperf 应用的权限相同的操作

## Hyperf 中使用

### 安装  [kukewang/](https://packagist.org/packages/kukewang/)hyperf_xxl_job

1. [composer地址](https://packagist.org/packages/kukewang/hyperf_xxl_job)，当前环境中需要有 git 环境，组件的依赖包需要使用 git 拉取

   `composer require kukewang/hyperf_xxl_job`

2. 发布配置文件

   `php bin/hyperf.php vendor:publish kukewang/hyperf_xxl_job`

3. 配置文件: `config/autoload/xxl_job.php`

   ```PHP
   return [
       // 是否启用 xxl_job
       'enable' => env('XXL_JOB_ENABLE', true),
       // XXL-JOB 服务端地址
       'admin_address' => env('XXL_JOB_ADMIN_ADDRESS', 'http://127.0.0.1:8080/xxl-job-admin'),
       // 对应的 AppName,xxl-job 创建的执行器 appName
       'app_name' => env('XXL_JOB_APP_NAME', 'xxl-job-demo'),
       // 访问凭证,执行器的访问凭证,如果配置，必填
       'access_token' => env('XXL_JOB_ACCESS_TOKEN', ''),
       // 执行器心跳间隔（秒）
       'heartbeat' => env('XXL_JOB_HEARTBEAT', 30),
       // 执行器 HTTP Server 相关配置
       'executor_server' => [
           // HTTP Server 路由前缀
           'prefix_url' => env('XXL_JOB_EXECUTOR_PREFIX_URL', 'php-xxl-job')
       ],
       'guzzle_config' => [
           'headers' => [
               'charset' => 'UTF-8',
           ],
           'timeout' => 10,
       ],
       'file_logger' => [
           'dir' => BASE_PATH . '/runtime/xxl_job/logs/',
       ],
   ];
   ```

4. .env 文件

   ```json
   # 是否开启 xxl-job
   XXL_JOB_ENABLE = true
   # XXL-JOB 服务端地址
   XXL_JOB_ADMIN_ADDRESS = http://xxl-job-admin.kukejs-dev.svc.cluster.local/xxl-job-admin
   # 对应的 AppName(执行器名称)
   XXL_JOB_APP_NAME = kukedatacenter
   # 访问凭证 (暂时为空，任务是 GLUE,则必须有访问令牌 )
   XXL_JOB_ACCESS_TOKEN =
   # 执行器心跳间隔（秒）
   XXL_JOB_HEARTBEAT = 30
   # 执行器 HTTP Server 相关配置，路由的 prefix
   XXL_JOB_PREFIX_URL = kukedatacenter-xxl-job
   ```



### Hyperf 框架创建任务类

1. 在 Hyperf 框架中创建任务类，并继承 `use Hyperf\XxlJob\Handler\AbstractJobHandler`
2. 实现 `execute` 方法，业务逻辑在此方法中编写
3. 添加注解 `@XxlJob(jobHandler="testJobHandler",init="init",destroy="destroy")`
    1. jobHandler 调度中心创建任务的 JobHandler![image-20211208140702602](https://cdn.learnku.com/uploads/images/202112/08/33853/PXnjbLBPGm.png!large)
    2. init 执行 `execute` 方法前的初始化方法，方法名自定义
    3. destory 执行 `execute` 方法后执行的方法，方法名自定义

```PHP
<?php

declare(strict_types=1);


namespace App\Command;

use Hyperf\XxlJob\Annotation\XxlJob;
use Hyperf\XxlJob\Handler\AbstractJobHandler;
use Hyperf\XxlJob\Requests\RunRequest;

/**
 * @XxlJob(jobHandler="testJobHandler",init="init",destroy="destroy")
 */
class TestCommand extends AbstractJobHandler
{
    public function init()
    {
        var_dump('init');
    }
    
    public function execute(RunRequest $request): void
    {
        var_dump(1);
    }
    
    public function destroy()
    {
        var_dump('destroy');
    }
}
```



### 调度中心配置

1. 创建执行器

   ![image-20211208142529463](https://cdn.learnku.com/uploads/images/202112/08/33853/2yMGpN7KNy.png!large)

   注册方式：自动注册

2. 创建任务

   ![image-20211208142855343](https://cdn.learnku.com/uploads/images/202112/08/33853/YZ98PIlnt3.png!large)

    1. 执行器 根据需要添加
    2. 填写 cron 规则
    3. 运行模式选择 `BEAN`
    4. JobHandler 自定义

3. 根据创建的 执行器 和 任务 填写 Hyperf 配置

    1. .env

   ![image-20211208143304563](https://cdn.learnku.com/uploads/images/202112/08/33853/0G8qpAui8n.png!large)

    2. 任务类

       ![image-20211208143543278](https://cdn.learnku.com/uploads/images/202112/08/33853/8TzB9Q0Ctu.png!large)



## 引用

> 关于 XXL-JOB 更多的使用细节可参考 [XXL-JOB 官方文档](https://www.xuxueli.com/xxl-job/#%E3%80%8A%E5%88%86%E5%B8%83%E5%BC%8F%E4%BB%BB%E5%8A%A1%E8%B0%83%E5%BA%A6%E5%B9%B3%E5%8F%B0XXL-JOB%E3%80%8B)

