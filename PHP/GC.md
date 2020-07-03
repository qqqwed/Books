# PHP5底层原理之垃圾回收机制

# 概念

**垃圾回收机制 是一种内存动态分配的方案，它会自动释放程序不再使用的已分配的内存块。** 

**垃圾回收机制** 可以让程序员不必过分关心程序内存分配，从而将更多的精力投入到业务逻辑。

>与之相关的一个概念，**内存泄露** 指的是程序未能释放那些已经不再使用的内存，造成内存的浪费。

那么 PHP 是如何实现垃圾回收机制的呢？



# PHP变量的内部存储结构

首先还是需要了解下基础知识，便于对垃圾回收原理内容的理解。

PHP **所有类型**的变量在底层都会以 **zval 结构体** 的形式实现 (源码文件Zend/zend.h)

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

注：上面 zval 结构体是 php5.3 版本之后的结构，php5.3 之前因为没有引入新的垃圾回收机制，即 **GC**，所以命名也没有`_gc`；而 php7 版本之后由于性能问题所以改写了 zval 结构，这里不再表述。

# 引用计数原理

## 变量容器

每个 PHP 变量存于一个叫 **zval** 的变量容器中。创建变量容器时，变量容器的 **ref_count**  初始值为 1， 每次被变量使用后，**ref_count + 1** 。当删除变量时(**unset( )**)，则它指向的变量容器的 **ref_count - 1** 。

#### 非 array 和 object 变量

每次将常量赋值给一个变量时，都会产生 <span style="color:red;font-weight:bold;">一个</span> 变量容器

举例：

```php
$a = 'new string';
xdebug_debug_zval('a');
```

结果会输出：

```php
a:(refcount=1, is_ref=0),string 'new string' (length=10)
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

## 赋值原理

### 写时复制原理

php 在设计的时候，为了节省内存，所以在变量之间赋值时，对于值相同的两个变量，会共用一块内存，也就是会在 **全局符号表** 内将变量 b 的**变量指针**指向变量 a **指向的同一个 zval 结构体**，而只有当其中一个变量的 zval 结构发生变化时，才会发生变量容器复制的内存变化，也因此叫做  <span style="color:#fe7821;font-weight:bold;">写时复制原理</span>。

<span style="color:#fe7821;font-weight:bold;">写时复制原理 </span>触发时机：

**php在修改一个变量时，如果发现变量的 refcount > 1，则会执行变量容器的内存复制**

举例：

```php
// 创建一个变量容器，变量 a 指向给变量容器，a 的 ref_count 为 1
$a = ['name' => 'string','number' => 3]; 	

// 变量 b 也指向变量 a 指向的变量容器，a 和 b 的 ref_count 为 2
$b = $a; 	
xdebug_debug_zval('a', 'b');
echo '<hr/>'
// 变量 b 的其中一个元素发生改变，此时会复制出一个新的变量容器，变量 b 重新指向新的变量容器，a 和 b 的ref_count 变成 1
$b['name'] = 'new string';	
xdebug_debug_zval('a', 'b'); 
```

结果输出：

```php
a:(refcount=2, is_ref=0),
array (size=2)
  'name' => (refcount=1, is_ref=0),string 'string' (length=6)
  'number' => (refcount=1, is_ref=0),int 3
b:(refcount=2, is_ref=0),
array (size=2)
  'name' => (refcount=1, is_ref=0),string 'string' (length=6)
  'number' => (refcount=1, is_ref=0),int 3
________________________________________________________________________________________  
a:(refcount=1, is_ref=0),
array (size=2)
  'name' => (refcount=1, is_ref=0),string 'string' (length=6)
  'number' => (refcount=2, is_ref=0),int 3
b:(refcount=1, is_ref=0),
array (size=2)
  'name' => (refcount=1, is_ref=0),string 'new string' (length=10)
  'number' => (refcount=2, is_ref=0),int 3
```



### 写时改变原理

上面说了普通赋值的情况，那么将引用赋值呢？

 先通过举例说明

```php
$a = ['name' => 'string','number' => 3]; 	
$b = &$a;
xdebug_debug_zval("a", "b");
```

结果输出

```php
a:(refcount=2, is_ref=1),
array (size=2)
  'name' => (refcount=1, is_ref=0),string 'string' (length=6)
  'number' => (refcount=1, is_ref=0),int 3
b:(refcount=2, is_ref=1),
array (size=2)
  'name' => (refcount=1, is_ref=0),string 'string' (length=6)
  'number' => (refcount=1, is_ref=0),int 3
```

此时，我们发现，变量 a 和 b 的 refcount 还是 2，只不过 is_ref 变成了 1，那是因为在将变量 a 引用赋值给变量b 时，在原变量容器上作了修改，将 is_ref 变成了 1，且 refcount + 1

那如果引用赋值的基础上又发生了变量的改变了呢？

```php
$a = ['name' => 'string','number' => 3]; 	
$b = &$a;
$b['name'] = "new string";
xdebug_debug_zval("a", "b");
```

结果输出：

```php
a:(refcount=2, is_ref=1),
array (size=2)
  'name' => (refcount=1, is_ref=0),string 'new string' (length=10)
  'number' => (refcount=1, is_ref=0),int 3
b:(refcount=2, is_ref=1),
array (size=2)
  'name' => (refcount=1, is_ref=0),string 'new string' (length=10)
  'number' => (refcount=1, is_ref=0),int 3
```

神奇的事情发生了，变量 b 和变量 a 的值一起发生改变了，其实这是因为触发了<span style="color:#fe7821;font-weight:bold;">写时改变原理</span>。

![image-20191029105237186](https://tva1.sinaimg.cn/large/006y8mN6ly1g8evryxsxgj30tl06qabe.jpg)



<span style="color:#fe7821;font-weight:bold;">写时改变原理 </span>触发时机：
**is_ref 为 1 的变量容器在被赋值之前，优先检查变量容器的 is_ref 是否等于 1 ，如果为 1，则不进行写时复制，而是在原变量容器基础上作内容修改；而如果将 is_ref 为 1 的变量容器赋值给其他变量时，则会立即触发 <span style="color:#fe7821;font-weight:bold;">写时改变原理 </span>**



现在将上面几个例子结合起来，又会是怎样的呢？

```php
$a = ['name' => 'string','number' => 3]; 	
$b = $a;
$c = &$a;
xdebug_debug_zval("a", "b", "c");
```

结果输出：

![image-20191029111149169](https://tva1.sinaimg.cn/large/006y8mN6ly1g8ewby4o51j30ei09oaau.jpg)

执行过程：

执行第一行：变量容器的 refcount 为 1

执行第二行：变量容器的 refcount 为 2，变量 a 和 变量 b 共享同一个变量容器

执行第三行：要将变量 a 引用赋值 给 变量 c，此时变量容器的 refcount > 1，如果要发生改变，会触发 <span style="color:#fe7821;font-weight:bold;">写时复制</span>，将变量 a 和 变量 b 分离，之后将变量 a 引用赋值给变量 c，则变量容器的 is_rel 变成 1，且 refcount 变成 2。

## 引用计数清 0

当变量容器的 **ref_count** 计数清 0 时，表示该变量容器就会被销毁，实现了内存回收。

这就是 <span style="color:red;font-weight:bold;">PHP 5.3 版本之前的垃圾回收机制。</span>

举例：

```php
$a = "new string";
$b = $a;
xdebug_debug_zval('a');
unset($b);		// 删除了符号表中的变量名 b，同时它指向的变量容器 ref_count -1
xdebug_debug_zval('a');
xdebug_debug_zval('b');
```

结果输出：

```php
a:(refcount=2, is_ref=0),string 'new string' (length=10)
a:(refcount=1, is_ref=0),string 'new string' (length=10)
b: no such symbol
```

## 循环引用引发的内存泄露问题

当我们添加一个 数组或对象 作为这个 数组或对象 的元素时，而如果此时删除了这个变量符号(unset)，此变量容器并不会被删除。因为其子元素还在指向该变量容器，但是由于所有作用域内没有任何符号指向这个变量容器，所以用户没有办法清除这个变量容器，结果就会导致内存泄露，直到该脚本执行结束被动清除这个变量容器。



举例：**把数组作为一个元素添加到自己**

```php
$a = array( 'one' );
$a[] = &$a;
xdebug_debug_zval( 'a' );
```

会输出：

```php
a:
(refcount=2, is_ref=1),
array (size=2)
  0 => (refcount=1, is_ref=0),string 'one' (length=3)
  1 => (refcount=2, is_ref=1),&array<
```

图示：

![12f37b1c6963c1c5c18f30495416a197-loop-array](https://tva1.sinaimg.cn/large/006y8mN6ly1g8bengbb5vj30et040wea.jpg)

能看到数组变量 **a** 同时也是这个数组的第二个元素「1」指向的变量容器中 **refcount** 为 **2**。上面的输出结果中的 **&array<** 意味着指向原始数组。

跟刚刚一样，对一个变量调用 unset，将删除这个符号，且它指向的变量容器中的引用次数也减 1。所以，如果我们在执行完上面的代码后，对变量 **a** 调用 unset , 那么变量 ​ **a** 和数组元素 「1」所指向的变量容器的引用次数减 1, 从 **2** 变成了 **1** . 下例可以说明:

```php
unset($a);
```

图示：

![12f37b1c6963c1c5c18f30495416a197-leak-array](https://tva1.sinaimg.cn/large/006y8mN6ly1g8bev6npn4j30cv040jr6.jpg)



如果上面的情况发生仅仅一两次倒没什么，但是如果出现几千次，甚至几十万次的内存泄漏，这显然是个大问题。这样的问题往往发生在长时间运行的脚本中，比如请求基本上不会结束的守护进程(deamons)或者单元测试中的大的套件(sets)中。

# 新的垃圾回收机制

PHP 5.3 版本之后引入 **根缓冲机制**，即 PHP 启动时默认设置指定 zval 数量的根缓冲区（默认是10000），当 PHP发现有存在 <span style="color:red;font-weight:bold;">循环引用</span> 的 zval 时，就会把其投入到根缓冲区，当根缓冲区达到配置文件中的指定数量（默认是10000）后，就会进行垃圾回收，以此解决循环引用导致的内存泄漏问题。

在 PHP 5.3 的 GC 中，针对的垃圾做了如下说明：

	1. 如果一个 zval 的 refcount 增加，那么此 zval 还在使用，肯定不是垃圾，不会进入缓冲区
 	2. 如果一个 zval的 refcount 减少到 0， 那么 zval 会被立即释放掉，不属于 GC 要处理的垃圾对象，不会进入缓冲区。
 	3. 如果一个 zval 的 refcount 减少之后大于0，那么此 zval 还不能被释放，此 zval 可能成为一个垃圾，将其放入缓冲区

### 垃圾回收算法

每当根缓存区存满时，PHP 会对根缓冲区的所有变量容器遍历进行 <span style="color:#fe7821;font-weight:bold;">模拟删除</span>，然后进行 <span style="color:#fe7821;font-weight:bold;">模拟恢复</span>。但是 PHP 只会对进行模拟删除后 **refcount > 0 的变量容器进行恢复**，那么没有进行恢复的也就是 **refcount = 0 的就是垃圾**了。

### 确认为垃圾的准则

1、如果引用计数减少到零，所在变量容器将被清除(free)，不属于垃圾
 2、如果一个zval 的引用计数减少后还大于0，那么它会进入垃圾周期。其次，在一个垃圾周期中，通过检查引用计数是否减1，并且检查哪些变量容器的引用次数是零，来发现哪部分是垃圾。



# 总结

垃圾回收机制：
 1、以 php 的引用计数机制为基础（ php5.3 以前只有该机制）
 2、同时使用根缓冲区机制，当 php 发现有存在循环引用的 zval 时，就会把其投入到根缓冲区，当根缓冲区达到配置文件中的指定数量后，就会进行垃圾回收，以此解决循环引用导致的内存泄漏问题（ php5.3 开始引入该机制）

# 参考资料

[PHP进阶学习之垃圾回收机制详解](http://www.phpxs.com/post/6608/)

[php底层原理之垃圾回收机制](https://juejin.im/post/5c7b785af265da2d8c7de5f1)

[引用计数基本知识](https://www.php.net/manual/zh/features.gc.refcounting-basics.php)