# PHP5垃圾回收机制

# 概念

**垃圾回收机制 是一种内存动态分配的方案，它会自动释放程序不再使用的已分配的内存块。** **垃圾回收机制** 可以让程序员不必过分关心程序内存分配，从而将更多的精力投入到业务逻辑。

>与之相关的一个概念，**内存泄露** 指的是程序未能释放那些已经不再使用的内存，造成内存的浪费。

那么 PHP 是如何实现垃圾回收机制的呢？



# PHP变量的内部存储结构

首先还是需要了解下基础知识，便于对垃圾回收原理内容的理解。

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

注：上面zval结构体是 php5.3 版本之后的结构，php5.3 之前因为没有引入新的垃圾回收机制，即 **GC**，所以命名也没有`_gc`；而 php7 版本之后由于性能问题所以改写了 zval 结构，这里不再表述。

# 引用计数原理

## 变量容器

每个 PHP 变量存于一个叫 **zval** 的变量容器中。创建变量容器时，变量容器的 **ref_count**  初始值为1， 每次被变量使用后，**ref_count + 1** 。当删除变量时(**unset( )**)，则它指向的变量容器的 **ref_count - 1** 。

#### 非 array 和 object 变量

每次将常量赋值给一个变量时，都会产生 <span style="color:red;font-weight:bold;">一个</span> 变量容器

举例：

```php
$a = 'new string';
xdebug_debug_zval('a');
```

结果会输出：

```php
a:
(refcount=1, is_ref=0),string 'new string' (length=10)
```

#### array 和 object 变量

每次将常量赋值给一个变量时，都会产生 <span style="color:red;font-weight:bold;">元素个数 +1 个</span> 变量容器

举例：

```php
$b = [
    'name' => 'new string',
    'number' => 12
];
xdebug_debug_zval('b');
```

结果会输出：

```php
b:
(refcount=1, is_ref=0),
array (size=2)
  'name' => (refcount=1, is_ref=0),string 'new string' (length=10)
  'number' => (refcount=1, is_ref=0),int 12
```

## 赋值原理(写时复制)

<font style="color:#F39019">将变量赋值给一个变量时，不会立即生成一个新的变量容器，而是当一个变量发生改变时,才会发生变量容器复制。</font>

举例：

```php
$a = [
    'name' => 'string',
    'number' => 3
]; //创建一个变量容器，变量a指向给变量容器，a的ref_count为1
$b = $a; //变量b也指向变量a指向的变量容器，a和b的ref_count为2
xdebug_debug_zval('a', 'b');
$b['name'] = 'new string';//变量b的其中一个元素发生改变，此时会复制出一个新的变量容器，变量b重新指向新的变量容器，a和b的ref_count变成1
xdebug_debug_zval('a', 'b'); 
```

结果输出：

```php
a:
(refcount=2, is_ref=0),
array (size=2)
  'name' => (refcount=1, is_ref=0),string 'string' (length=6)
  'number' => (refcount=1, is_ref=0),int 3
b:
(refcount=2, is_ref=0),
array (size=2)
  'name' => (refcount=1, is_ref=0),string 'string' (length=6)
  'number' => (refcount=1, is_ref=0),int 3
a:
(refcount=1, is_ref=0),
array (size=2)
  'name' => (refcount=1, is_ref=0),string 'string' (length=6)
  'number' => (refcount=2, is_ref=0),int 3
b:
(refcount=1, is_ref=0),
array (size=2)
  'name' => (refcount=1, is_ref=0),string 'new string' (length=10)
  'number' => (refcount=2, is_ref=0),int 3
```

## 引用计数清 0

当变量容器的 **ref_count** 计数清 0 时，表示该变量容器就会被销毁，实现了内存回收。这就是 <span style="color:red;font-weight:bold;">PHP 5.3 版本之前的垃圾回收机制。</span>

# 参考资料

[PHP进阶学习之垃圾回收机制详解](http://www.phpxs.com/post/6608/)

[php底层原理之垃圾回收机制](https://juejin.im/post/5c7b785af265da2d8c7de5f1)