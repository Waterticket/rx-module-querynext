<?php

namespace Rhymix\Modules\Querynext\Controllers;

use Rhymix\Framework\Cache;
use Rhymix\Framework\DB;
use Rhymix\Framework\Exception;
use Rhymix\Framework\Storage;
use Rhymix\Modules\Querynext\Models\Config as ConfigModel;
use BaseObject;
use Context;

/**
 * QueryNext
 *
 * Copyright (c) Waterticket
 *
 * Generated with https://www.poesis.dev/tools/rxmodulegen
 */
class Admin extends Base
{
	/**
	 * 초기화
	 */
	public function init()
	{
		// 관리자 화면 템플릿 경로 지정
		$this->setTemplatePath($this->module_path . 'views/admin/');
	}

	/**
	 * 관리자 설정 화면 예제
	 */
	public function dispQuerynextAdminConfig()
	{
		// 현재 설정 상태 불러오기
		$config = ConfigModel::getConfig();

		// Context에 세팅
		Context::set('config', $config);

		// 스킨 파일 지정
		$this->setTemplateFile('config');
	}

	/**
	 * 관리자 설정 저장 액션 예제
	 */
	public function procQuerynextAdminInsertConfig()
	{
		// 현재 설정 상태 불러오기
		$config = ConfigModel::getConfig();

		// 제출받은 데이터 불러오기
		$vars = Context::getRequestVars();

		// 제출받은 데이터를 각각 적절히 필터링하여 설정 변경
		if (in_array($vars->example_config, ['Y', 'N']))
		{
			$config->example_config = $vars->example_config;
		}
		else
		{
			return new BaseObject(-1, '설정값이 이상함');
		}

		// 변경된 설정을 저장
		$output = ConfigModel::setConfig($config);
		if (!$output->toBool())
		{
			return $output;
		}

		// 설정 화면으로 리다이렉트
		$this->setMessage('success_registed');
		$this->setRedirectUrl(Context::get('success_return_url'));
	}
}
