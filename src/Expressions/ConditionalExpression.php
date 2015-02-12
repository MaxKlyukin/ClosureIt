<?php

namespace ClosureIt\Expressions;


class ConditionalExpression implements Expression
{
    const D_AND = 'and';
    const D_OR = 'or';

    /**
     * @var ConditionalPrimaryExpression[]
     */
    public $conditionalPrimaries;

    /**
     * @var string
     */
    public $delimiter;

    /**
     * @var boolean
     */
    public $not;

    function __construct($conditionalPrimaries, $delimiter, $not)
    {
        $this->conditionalPrimaries = $conditionalPrimaries;
        $this->delimiter = $delimiter;
        $this->not = $not;
    }


}