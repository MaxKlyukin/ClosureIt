<?php

namespace ClosureIt\Expressions;


class ConditionalPrimaryExpression implements Expression
{

    /**
     * @var Expression|null
     */
    public $simpleConditionalExpression;

    /**
     * @var ConditionalExpression|ConditionalPrimaryExpression|null
     */
    public $conditionalExpression;

}