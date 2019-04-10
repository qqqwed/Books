<?php

/**
 * 从不同的源收集文件
 * 可以从远程URL读取HTML、可以读取流资源、也可以收集终端的输出
 */
class DocumentStore
{
	protected $data = [];

	public function addDocument(Documentable $document)
	{
		$key = $document->getId();
		$value = $document->getContent();
		$this->data[$key] = $value;
	}
	
	public function getDocuments()
	{
		return $this->data;
	}
}