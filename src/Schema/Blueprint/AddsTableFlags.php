<?php

namespace SingleStore\Laravel\Schema\Blueprint;

trait AddsTableFlags
{
    public bool $rowstore = false;

    public bool $reference = false;

    public bool $global = false;

    public bool $sparse = false;

    public function rowstore(): static
    {
        $this->rowstore = true;

        return $this;
    }

    public function reference(): static
    {
        $this->reference = true;

        return $this;
    }

    public function temporary($global = false): static
    {
        $this->global = $global;
        $this->temporary = true;

        return $this;
    }

    public function global(): static
    {
        $this->global = true;

        return $this;
    }

    public function sparse(): static
    {
        $this->sparse = true;

        return $this;
    }
}
