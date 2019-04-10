<?php
require 'Documentable.php';
require 'DocumentStore.php';
require 'HtmlDocument.php';
require 'StreamDocument.php';
require 'CommandOutputDocument.php';

/**
 * 从不同的源收集文件
 * 可以从远程URL读取HTML、可以读取流资源、也可以收集终端的输出
 */
$documentStore = new DocumentStore();

// 添加HTML文档
$htmlDoc = new HtmlDocument('https://www.baidu.com');
$documentStore->addDocument($htmlDoc);

// 添加流文档
$streamDoc = new StreamDocument(fopen('stream.txt', 'rb'));
$documentStore->addDocument($streamDoc);

// 添加终端命令控制文档
$cmdDoc = new CommandOutputDocument('cat /etc/hosts');
$documentStore->addDocument($cmdDoc);

print_r($documentStore->getDocuments());