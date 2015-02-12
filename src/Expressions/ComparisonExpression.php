<?php

namespace ClosureIt\Expressions;


class ComparisonExpression implements Expression
{

    /**
     * @var LiteralExpression|ParamFieldExpression|VariableExpression
     */
    public $firstParam;

    /**
     * @var mixed
     */
    public $comparisonOperator;

    /**
     * @var Expression
     */
    public $secondParam;
}