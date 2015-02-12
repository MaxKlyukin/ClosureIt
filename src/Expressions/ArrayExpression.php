<?php

namespace ClosureIt\Expressions;


class ArrayExpression implements ParamExpression
{

    /**
     * @var LiteralExpression[]
     */
    public $literals;

    function __construct($literals)
    {
        $this->literals = $literals;
    }
}