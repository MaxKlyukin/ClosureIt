<?php

namespace ClosureIt\Expressions;


class LiteralExpression implements ParamExpression
{

    const STRING = 'string';
    const BOOLEAN = 'boolean';
    const NUMERIC = 'numeric';
    const NULL = 'null';

    public $type;
    public $value;


    public function __construct($type, $value)
    {
        $this->type = $type;
        $this->value = $value;
    }
}