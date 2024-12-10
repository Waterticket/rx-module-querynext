<?php

namespace Rhymix\Modules\Querynext\Models;

use ModuleController;
use ModuleModel;

/**
 * QueryNext
 * 
 * Copyright (c) Waterticket
 * 
 * Generated with https://www.poesis.dev/tools/rxmodulegen
 */
class Config
{
	/**
	 * 모듈 설정 캐시를 위한 변수.
	 */
	protected static $_cache = null;
	
	/**
	 * 모듈 설정을 가져오는 함수.
	 * 
	 * 캐시 처리되기 때문에 ModuleModel을 직접 호출하는 것보다 효율적이다.
	 * 모듈 내에서 설정을 불러올 때는 가급적 이 함수를 사용하도록 한다. 
	 * 
	 * @return object
	 */
	public static function getConfig()
	{
		if (self::$_cache === null)
		{
			self::$_cache = ModuleModel::getModuleConfig('querynext') ?: new \stdClass;
		}
		return self::$_cache;
	}
	
	/**
	 * 모듈 설정을 저장하는 함수.
	 * 
	 * 설정을 변경할 필요가 있을 때 ModuleController를 직접 호출하지 말고 이 함수를 사용한다.
	 * getConfig()으로 가져온 설정을 적절히 변경하여 setConfig()으로 다시 저장하는 것이 정석.
	 * 
	 * @param object $config
	 * @return object
	 */
	public static function setConfig($config)
	{
		$oModuleController = ModuleController::getInstance();
		$result = $oModuleController->insertModuleConfig('querynext', $config);
		if ($result->toBool())
		{
			self::$_cache = $config;
		}
		return $result;
	}
}
