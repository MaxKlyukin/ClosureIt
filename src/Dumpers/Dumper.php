<?php

namespace ClosureIt\Dumpers;


use ClosureIt\Result;

interface Dumper
{
    function __construct(Result $result);

    public function dump();
}