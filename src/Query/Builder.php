<?php

namespace SingleStore\Laravel\Query;

use Illuminate\Database\Query\Builder as BaseBuilder;

class Builder extends BaseBuilder
{
    public array $options;

    public function options(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function toSql(): string
    {
        $sql = parent::toSql();

        if (! empty($this->options)) {
            $sql .= ' '.$this->grammar->compileOptions($this->options);
        }

        return $sql;
    }
}
