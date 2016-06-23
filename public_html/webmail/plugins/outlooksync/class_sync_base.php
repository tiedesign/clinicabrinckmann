<?php
/*
 * AfterLogic WebMail Pro PHP by AfterLogic Corp. <support@afterlogic.com>
 *
 * Copyright (C) 2002-2011  AfterLogic Corp. (www.afterlogic.com)
 * Distributed under the terms of the license described in COPYING
 *
 */

/**
 * Class SyncCalendar.
 * Gives all functions for calendar sync.
 */

defined('WM_ROOTPATH') || define('WM_ROOTPATH', (dirname(__FILE__).'/../../'));

require_once(WM_ROOTPATH.'plugins/outlooksync/configuration.php');

class SyncBase
{
	protected $managerPath;
	protected $managerName;
	protected $manager;
	protected $userId;
	protected $account;
	public $responseStatus;
	public $response;

	public function __construct()
	{
		$this->account = null;
		$this->userId = null;
		$this->responseStatus = X_WMP_RESPONSE_STATUS_OK;
		$this->response = '';
	}

	/**
	 * @param CAccount $account
	 * @param string $action
	 */
	protected function init($account, $action)
	{
		$this->account = $account;
		$this->userId = $account->IdUser;
		$this->action = $action;
		require_once($this->managerPath);
		$this->manager = new $this->managerName;
	}
}