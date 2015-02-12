<?php

namespace ClosureIt\Expressions;


class ParamFieldExpression implements ParamExpression
{

    public $paramName;
    public $fieldName;
    public $isFunction;

    function __construct($paramName, $fieldName, $isFunction)
    {
        $this->paramName = $paramName;
        $this->fieldName = $fieldName;
        $this->isFunction = $isFunction;
    }

    public function getFinalFieldName()
    {
        $fieldName = $this->fieldName;
        if ($this->isFunction && strpos($fieldName, 'get') === 0) {
            $fieldName = substr($fieldName, 3);
        }

        return mb_strtolower($fieldName);
    }
}