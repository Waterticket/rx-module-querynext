<?php

namespace Rhymix\Modules\Querynext\Controllers;

use Rhymix\Modules\Querynext\Models\Jpa\JpaRepository;
use Rhymix\Modules\Querynext\Models\Jpa\JpaRepositoryProxy;

/**
 * QueryNext
 * 
 * Copyright (c) Waterticket
 * 
 * Generated with https://www.poesis.dev/tools/rxmodulegen
 */
class EventHandlers extends Base
{
	/**
	 * 트리거 예제: 새 글 작성시 실행
	 * 
	 * 주의: 첨부파일이 있는 경우 아직 작성하지 않았어도 이 함수가 실행될 수 있음
	 * 
	 * @param object $obj
	 */
	public function repositoryAutoloaderInitialize($obj)
	{
		spl_autoload_register(function ($class) {
			if (interface_exists($class) && is_subclass_of($class, \Rhymix\Modules\Querynext\Models\Jpa\JPARepository::class)) {
				// 특정 인터페이스 호출을 프록시 클래스로 연결
				class_alias(\Rhymix\Modules\Querynext\Models\Jpa\JPARepositoryProxy::class, $class);
			}
		});
	}
}
