# 开发环境

## 安装

### ubuntu安装最新版node和npm

***Step 1 先在系统上安装好 nodejs 和 npm***

```shell
sudo apt-get install nodejs-legacy
sudo apt-get install npm
```

安装好之后，是node和npm适合ubuntu系统的最新版本，但不是node和npm的最新版本
有些东西，由于node和npm适应于ubuntu系统的版本无法满足下载的包，所以你得升级。

***Step 2 安装用于安装 nodejs 的模块 n***

```shell
sudo npm install -g n
```

***Step 3 通过n模块安装指定的 nodejs ( latest / stable / lts)***

```shell
sudo n latest
```

***Step 4  升级npm为最新版本***

```shell
sudo npm install npm@latest -g
```

***Step 5 查看版本***

```shell
node -v
npm -v
```



### Mac OS 上安装

你可以通过以下两种方式在 Mac OS 上来安装 node：

- 1、在官方下载网站下载 pkg 安装包，直接点击安装即可。

- 2、使用 brew 命令来安装：

    ```shell
    brew install node
    ```

### Linux 上安装 Node.js

**直接使用已编译好的包**

Node 官网已经把 linux 下载版本更改为已编译好的版本了，我们可以直接下载解压后使用：

```shell
# wget https://nodejs.org/dist/v10.9.0/node-v10.9.0-linux-x64.tar.xz    // 下载
# tar xf  node-v10.9.0-linux-x64.tar.xz       // 解压
# cd node-v10.9.0-linux-x64/                  // 进入解压目录
# ./bin/node -v                               // 执行node命令 查看版本
v10.9.0
```

解压文件的 bin 目录底下包含了 node、npm 等命令，我们可以使用 ln 命令来设置软连接：

```shell
ln -s /usr/software/nodejs/bin/npm   /usr/local/bin/ 
ln -s /usr/software/nodejs/bin/node   /usr/local/bin/
```

## 升级

node有一个模块叫n，是专门用来管理node.js的版本。

```shell
# 查看版本
node -v 
# 清除npm cache
npm cache clean -force
# 安装n模块
npm install -g n 
# 不行就这样：npm install -g n -f
# 升级新版本（稳定版 stable ,最新版 latest ,指定版本号v8.11.1）
n latest
# 查看版本号，确认是否升级成功
node -v 
```



## FAQ

### node npm install Error: CERT_UNTRUSTED

ssl验证问题，使用下面的命令取消ssl验证即可解决

```she
npm config set strict-ssl false
```

### error-code-ELIFECYCLE

执行如下命令

```she
npm cache clear --force
npm install -g npm
```

