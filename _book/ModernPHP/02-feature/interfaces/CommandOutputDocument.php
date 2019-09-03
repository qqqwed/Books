<?php
/**
 * 获取终端命令的执行结果
 */
class CommandOutputDocument implements Documentable
{
	protected $command;

	public function __construct($command)
	{
		$this->command = $command;
	}

	public function getId()
	{
		return $this->command;
	}

	public function getContent()
	{
		return shell_exec($this->command);
	}
}
