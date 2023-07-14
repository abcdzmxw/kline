<?php

namespace App\Libs\Ethtool;

class Callback
{
    function __invoke($error, $result)
    {
        if ($error) throw $error;
        $this->result = $result;
    }
}
