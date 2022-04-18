<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace SingleStore\Laravel\Schema;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint as BaseBlueprint;
use Illuminate\Database\Schema\Grammars\Grammar;
use SingleStore\Laravel\Schema\Blueprint\InlinesIndexes;
use SingleStore\Laravel\Schema\Blueprint\ModifiesIndexes;
use SingleStore\Laravel\Schema\Blueprint\AddsTableFlags;

class Blueprint extends BaseBlueprint
{
    use AddsTableFlags, ModifiesIndexes, InlinesIndexes;

    public const INDEX_PLACEHOLDER = '__singlestore_indexes__';

    public function toSql(Connection $connection, Grammar $grammar)
    {
        $statements = parent::toSql($connection, $grammar);

        return $this->creating() ? $this->inlineCreateIndexStatements($statements) : $statements;
    }

}