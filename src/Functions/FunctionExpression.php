<?php

namespace ClosureIt\Functions;


use ClosureIt\Expressions\Expression;
use ClosureIt\Parser;

abstract class FunctionExpression implements Expression
{
    public $not = false;

    abstract public function parse(Parser $parser);
}