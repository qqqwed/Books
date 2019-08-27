# 安装扩展

## 查找 php.ini 的位置

### Style 1 显示配置文件名

```shell
php --ini
```
<img referrer="no-referrer|origin|unsafe-url" src="https://ws1.sinaimg.cn/large/006y8mN6ly1g6e1lqxjkuj310k06674w.jpg">

### Style 2 命令行打印 phpinfo 查找 关键字 php.ini

```shell
php -r "phpinfo();" | grep php\.ini
```

![image-20190827093941051](https://ws3.sinaimg.cn/large/006y8mN6ly1g6dzmnpszdj30xq04gdgi.jpg)

### Style 3 phpinfo 信息 查找 关键字 Loaded Configuration File

```shell
php -i | grep "Loaded Configuration File"
```

![image-20190827094219758](https://ws1.sinaimg.cn/large/006y8mN6ly1g6dzpexx9pj30ws038q3b.jpg)

### Style 4 通过浏览器访问 phpinfo() 查看

新建一个 index.php 文件，然后浏览器访问这个文件

```php
<?php
    phpinfo();
```

![image-20190827094829552](https://ws1.sinaimg.cn/large/006y8mN6ly1g6dzvtu3wmj31fq05kq47.jpg)

