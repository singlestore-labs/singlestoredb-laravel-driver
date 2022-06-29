<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace SingleStore\Laravel\Schema\Blueprint;

trait AddsTableFlags
{
    /**
     * @var bool
     */
    public $rowstore = false;

    /**
     * @var bool
     */
    public $reference = false;

    /**
     * @var bool
     */
    public $global = false;

    /**
     * @var bool
     */
    public $sparse = false;

    public function rowstore()
    {
        $this->rowstore = true;

        return $this;
    }

    public function reference()
    {
        $this->reference = true;

        return $this;
    }

    public function temporary($global = false)
    {
        $this->global = $global;
        $this->temporary = true;

        return $this;
    }

    public function global()
    {
        $this->global = true;

        return $this;
    }

    public function sparse()
    {
        $this->sparse = true;

        return $this;
    }
}
