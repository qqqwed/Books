

# PHP5底层原理之变量

# 变量结构

## zval 结构体

PHP **所有类型**的变量在底层都会以 **zval 结构体**的形式实现 (源码文件Zend/zend.h)

源码根目录搜索

`grep -rin --color --include=*.h --include=*.c _zval_struct *`

```c
struct _zval_struct {
	/* Variable information */
	zvalue_value value;		/* 变量value值 */
	zend_uint refcount__gc; /* 引用计数内存中使用次数，为0删除该变量 */
	zend_uchar type;	/* 变量类型 */
	zend_uchar is_ref__gc; /* 区分是否是引用变量，是引用为1，否则为0 */
};
```

注：上面zval结构体是 php5.3 版本之后的结构，php5.3 之前因为没有引入新的垃圾回收机制，即 GC，所以命名也没有`_gc`；而 php7 版本之后由于性能问题所以改写了 zval 结构，这里不再表述

## zval 组成

上面结构体内容可以看出每一个 PHP 变量都会由 `变量类型`、`value值`、`引用计数次数`和`是否是引用变量` 四部分组成

- **type** 变量类型

    >type 的值为以下常量：
    >IS_NULL, IS_BOOL, IS_LONG, IS_DOUBLE, IS_STRING, IS_ARRAY, IS_OBJECT, IS_RESOURCE 

- **value** 值

    > 因为要存储多种类型，所以 value 是一个 union，也由此实现了弱类型

    ```c
    typedef union _zvalue_value {
       long lval;             /* long value */
       double dval;            /* double value */
       struct {
          char *val;
          int len;
       } str;
       HashTable *ht;          /* hash table value */
       zend_object_value obj;
    } zvalue_value;
    ```

- **refcount__gc**  引用计数次数
- **is_ref__gc** 是否是引用变量



# 变量类型

看到这里，可能会有小伙伴们问我，PHP 不是有 8 种数据类型吗？但是为什么对应的 zval 的 value 值只有 5 种？

原因是这样的，PHP 出于对内存节省的考虑，所以对于一些变量类型做了复用，并没有一一对应去定义每个变量类型

下面我们看一下 zval 的每个 value 值所对应的变量类型

```
zval.value.lval => 整型、布尔型、资源
zval.value.dval => 浮点型
zval.value.str  => 字符串
zval.value.*ht  => 数组
zval.value.obj  => 对象
```

看到这里大家可能会比较奇怪，「布尔型」和「资源」是怎么对应到 zval.value 的 lval 上的呢？还有，NULL呢？

**布尔型**
就像我们会将 true 和 false 映射成 0 和 1 进行数据库存储一样，PHP 也是这么做的。所以 PHP 发现 zval 的type 值是「布尔型」时，会将「布尔型」转成 0 或 1 存储在 zval.value 的 lval 中

```c
zval.type = IS_BOOL
zval.value.lval = 1/0
```

**资源**
「资源」对于 PHP 来说属于一个比较特殊的变量，而 PHP 会将每个「资源」对应的「**资源标识编号**」存储在 zval.value 的 lval 中。常见的资源有：文件句柄、数据库句柄等

```c
zval.type = IS_RESOURCE
zval.value.lval = 资源标识编号
```

**NULL**
对于 NULL 来说，就更好理解了，因为本身通过 zval 的 type 值即可区分，所以并没有将 NULL 值存储在 zval 的 value 中

```c
zval.type = IS_NULL  
```



# 变量生成

PHP 作为一门动态语言，没有先声明变量后赋值的习惯，所以都是拿来一个常量变量直接就进行了赋值，那么是如何实现的呢？

举例：

```php
$name = "new string";
```

### 变量容器生成

其实每次变量被常量赋值时，都会对应生成一个变量容器。刚才的例子会生成一个变量容器，容器的 **type** 是字符串类型，而 value 值则是『new string』，且此时该变量容器的 **ref_count** 会加 1

### 变量名和变量容器关联

而变量 **name** 是如何与变量容器关联起来的呢？其实也是使用了 PHP 的一个内部机制，即 <span style="color:#EC5D57;font-weight:bold;">哈希表</span>。每个变量的**变量名** 和指向 zval 结构的 **指针** 被 **存储** 在 <span style="color:#EC5D57;font-weight:bold;">哈希表</span> 内，以此实现了变量名到变量容器的映射。

# 变量作用域

上面我们提到了「变量名」和「变量容器」映射的概念。对于 PHP 来说，变量有 **全局变量** 和 **局部变量** 之分；那么，他们都是存储到一个 <span style="color:#EC5D57;font-weight:bold;">哈希表</span> 内了么？

其实不是的，变量存储也有作用域的概念。

**全局变量** 被存储到了 <span style="color:#EC5D57;font-weight:bold;">全局符号表</span> 内，而 **局部变量** 也就是指函数或对象内的变量，则被存储到了 <span style="color:#EC5D57;font-weight:bold;">活动符号表</span> 内（每个函数或对象都单独维护了自己的活动符号表。活动符号表的生命周期，从函数或对象被调用时开始，到调用完成时结束）

# 变量销毁

变量销毁，分为以下几种情况：
 1、手动销毁
 2、垃圾回收机制销毁（引用计数清0销毁和根缓冲区满后销毁）

我们这次主要讲一下手动销毁，即 **unset**，每次销毁时都会将符号表内的 **变量名** 和对应的 **zval 结构** 进行销毁，并将对应的内存归还到 PHP 所维护的内存池内（按内存大小划分到对应内存列表中）


而对于垃圾回收机制的销毁，请看下篇文章[php底层原理之垃圾回收机制](https://juejin.im/post/5c7b785af265da2d8c7de5f1)

# 参考资料：

[PHP底层原理分析（一）：PHP变量的底层实现](http://blog.yzmcms.com/html/php/173.html)

[php底层原理之变量（一）](https://juejin.im/post/5c8e24c2e51d4536485a1070)