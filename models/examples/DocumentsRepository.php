<?php

namespace Rhymix\Modules\Querynext\Models\Examples;

use Rhymix\Modules\Querynext\Models\Jpa\JpaRepository;
use Rhymix\Modules\Querynext\Models\Jpa\Attributes\Table;

#[Table("documents")]
interface DocumentsRepository extends JpaRepository
{
    public static function findByMemberSrl(int $member_srl): array;
}