<?php

namespace ClosureIt;

class Result
{

    public $expression;
    public $params;
    public $variables;

    function __construct($expression, $params, $variables)
    {
        $this->expression = $expression;
        $this->params = $params;
        $this->variables = $variables;
    }


}