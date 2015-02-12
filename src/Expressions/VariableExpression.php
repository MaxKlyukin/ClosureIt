<?php

namespace ClosureIt\Expressions;


class VariableExpression implements ParamExpression
{

    public $name;

    function __construct($name)
    {
        $this->name = $name;
    }


}