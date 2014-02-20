### REST简介

REST的流程很简单，获取Request单例，然后执行exec方法，该方法里会调用Route来解析URI获取相应的Resource，
然后实例化Resource，触发相应的HTTP方法，最后返回一个Response对象，Response执行output方法就输出了结果。
听起来好像一点都不简单，哈哈，还是来大概看一下代码吧

	Rest_Request::instance()->exec()->output();

### REST特性

#### 轻量级

REST包含了核心的Request/Resource/Response/Route/Config/Validation功能，常用的memcache/mysqli的部件，不包含其他如Session/View等，如果有需要可以自己实现。
一个工具应该把一件事做好，同时提供接口，这也是REST的哲学。

#### 使用方便

使用时，URI不需要定义，请求资源的最后一个为资源名，然后编写Resource就行了，其他的事REST会帮你搞定。
例如:
请求资源为 /users/11 则自动路由到 classes/resource/users.php //id 可以在请求数据中获取
请求资源为 /users/11/relationships/22 则自动路由到 classes/resource/users/relationships.php //修饰资源属性可以通过users单数加_id即user_id在请求数据中获取

如果请求资源为 / （根目录）可以在config/route.php配置默认路由到哪个资源文件，
config/route.php
return array(
	'default' => 'welcome', //默认资源
);

当请求资源为动词或名词单数时采用RPC风格解释。
例如：
请求资源为 /oauth2/token 则自动路由到 classes/resource/oauth2.php 中的token方法
请求资源为 /search 则自动路由post到 classes/resource/search.php 中的post方法

    resource/welcome.php
    /**
     * 需要oauth2认证，继承Oauth2_Resource
     * 不需要oauth2认证，继承Rest_Resource
     */
    class Resource_Welcome extends Rest_Resource
    {
        public function get()
        {
            $this->response->set_body(array('data' => 'welcome', 'method' => __FUNCTION__));
        }
    }

每一个资源对应8个http方法(除RPC风格)。
get_list\put_list\post_list\delete_list 对列表操作
get\put\post\delete 对单个资源操作

#### 扩展

如果有需要可以根据实际情况修改。

#### Config功能

config文件如上面所示，就是返回一个数组。使用也很简单:

// 获取config/cache.php文件的default key对应的内容
    Rest_Config::get('cache.default');

// 设置config(不会写入到文件，只在一个http request有效)
    Rest_Config::set('message.example.id.digit', 'id can be anything');

#### 其他
对于不支持PUT和DELETE方法的客户端，可以在请求参数上加上?_method=PUT 或 ?_method=DELETE

对于不能接收非200状态码内容的客户端，可以在请求参数上加上?_suppress_response_codes=true 来强制响应状态码为200，
返回的状态码需要解析返回数据中根节点的response_code

正确响应

    {
      "response_code": 200,
      "data": "welcome"
    }
错误响应

    {
      "response_code": 400,
      "error": "invalid_request",  //oauth2规定错误消息必须在顶级节点且名称是error，错误消息在限定范围
      "error_description": "The access token was not found.", //oauth2 可选错误描述信息
      "trace": "OAuth2_Exception_Authenticate [ 0 ]: invalid_request ~ MOD_PATH/oauth2/classes/oauth2.php [ 486 ]"
    }

#### 接口参考格式

资源操作

    Resource    POST(create)         GET(read)     PUT(update)                       DELETE(delete)
    /dogs       Create a new dog     List dogs     Bulk update dogs                  Delete all dogs
    /dogs/1234  Error                Show Bo       If exists Bo update,If not error  Delete Bo


API请求和响应的格式

	Create a brown dog named Al
	POST /dogs
	name=Al&furColor=brown
	Response
	200 OK

	{
	"id": "1234",
	"name": "Al",
	"fur_color": "brown"
	}


	Rename Al to Rover - Update
	PUT /dogs/1234
	name=Rover
	Response
	200 OK

	{
	"id":"1234",
	"name": "Rover",
	"fur_color": "brown"
	}


	Tell me about a particular dog
	GET /dogs/1234
	Response
	200 OK

	{
	"id":"1234",
	"name": "Rover",
	"fur_color": "brown"
	}

	Tell me about all the dogs
	GET /dogs
	Response
	200 OK

	{
	"data":
	[{
	"id":"1233",
	"name": "Fido",
	"fur_color": "white"},
	{
	"id":"1234",
	"name": "Rover",
	"fur_color": "brown"}]
	"meta":
	[{"total_count":327,"limit":25,"offset":100}]
    }


	Delete Rover :-(
	DELETE /dogs/1234
	Response
	200 OK

#### nginx rewrite设置
    //gzip 压缩需要增加对application/json支持。

    server
    {
        listen       80;
        server_name  resty.test.com;
        index index.html index.htm index.php;
        set $root_path /var/www/html/resty/app;
        root  $root_path;

        #error_page  404              /404error/404.html;
        #error_page  500 502 503 504  /404error/50x.html;
        location = /favicon.ico {
                log_not_found off;
        }

        location / {
            index  index.php index.html index.htm;
            if (!-e $request_filename) {
                rewrite ^/(.*)$ /index.php last;
            }
        }

        location ~ .*\.php?$
        {
               include fastcgi_params;
                fastcgi_pass  127.0.0.1:9000;
                fastcgi_index index.php;
                fastcgi_connect_timeout 60;
                fastcgi_send_timeout 180;
                fastcgi_read_timeout 180;
                fastcgi_buffer_size 128k;
                fastcgi_buffers 4 256k;
                fastcgi_busy_buffers_size 256k;
                fastcgi_temp_file_write_size 256k;
                fastcgi_intercept_errors on;
                fastcgi_param  SCRIPT_FILENAME  $root_path$fastcgi_script_name;
        }

        #error_log  /data/logs/resty.test.com-error.log;
        #access_log  /data/logs/resty.test.com-aceess.log main;

    }

#### TODO:

#####接口调用限制
#####接口统计（错误、性能、可用性、限额）
#####安全
#####数据保护
