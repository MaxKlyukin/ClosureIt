<?php

namespace ClosureIt\Dumpers;

use ClosureIt\Expressions\ArrayExpression;
use ClosureIt\Expressions\ComparisonExpression;
use ClosureIt\Expressions\ConditionalExpression;
use ClosureIt\Expressions\ConditionalPrimaryExpression;
use ClosureIt\Expressions\Expression;
use ClosureIt\Expressions\LiteralExpression;
use ClosureIt\Expressions\ParamFieldExpression;
use ClosureIt\Expressions\VariableExpression;
use ClosureIt\Functions\FunctionExpression;
use ClosureIt\Functions\InArrayFunction;
use ClosureIt\Result;

class SqlDumper implements Dumper
{

    /**
     * @var Result
     */
    private $result;

    private $autoVar = 0;

    function __construct(Result $result)
    {
        $this->result = $result;
    }

    public function dump()
    {
        $this->autoVar = 0;

        return $this->dumpExpression($this->result->expression);
    }

    /**
     * @param Expression $expression
     * @return string
     * @throws DumperException
     */
    private function dumpExpression(Expression $expression)
    {
        switch (get_class($expression)) {
            case ConditionalExpression::class:
                /** @var ConditionalExpression $expression */
                return $this->dumpConditionalExpression($expression);
            case ConditionalPrimaryExpression::class:
                /** @var ConditionalPrimaryExpression $expression */
                return $this->dumpConditionalPrimary($expression);
            case ComparisonExpression::class:
                /** @var ComparisonExpression $expression */
                return $this->dumpComparisonExpression($expression);
            case ParamFieldExpression::class:
                /** @var ParamFieldExpression $expression */
                return $this->dumpParamField($expression);
            case ArrayExpression::class:
                /** @var ArrayExpression $expression */
                return $this->dumpArrayExpression($expression);
            case VariableExpression::class:
                /** @var VariableExpression $expression */
                return $this->dumpVariableExpression($expression);
            case LiteralExpression::class:
                /** @var LiteralExpression $expression */
                return $this->dumpLiteral($expression);
            default:
                if ($expression instanceof FunctionExpression) {
                    /** @var FunctionExpression $expression */
                    return $this->dumpFunctionExpression($expression);
                }
                throw new DumperException("Unknown expression");
        }
    }

    /**
     * @param ConditionalExpression $expression
     * @return string
     */
    private function dumpConditionalExpression(ConditionalExpression $expression)
    {
        $conditionalPrimaryExpressions = array_map(function (ConditionalPrimaryExpression $primary) {
            return $this->dumpExpression($primary);
        }, $expression->conditionalPrimaries);

        $delimiter = strtoupper($expression->delimiter);

        return implode(" {$delimiter} ", $conditionalPrimaryExpressions);
    }

    /**
     * @param ConditionalPrimaryExpression $expression
     * @return string
     */
    private function dumpConditionalPrimary(ConditionalPrimaryExpression $expression)
    {
        $conditionalExpression = $expression->conditionalExpression;
        if ($conditionalExpression) {
            return sprintf(
                "%s(%s)",
                isset($conditionalExpression->not) && $conditionalExpression->not ? "NOT " : "",
                $this->dumpExpression($conditionalExpression)
            );

        } else {
            return $this->dumpExpression($expression->simpleConditionalExpression);
        }
    }

    /**
     * @param ComparisonExpression $expression
     * @return string
     */
    private function dumpComparisonExpression(ComparisonExpression $expression)
    {
        $comparisonOperator = $expression->comparisonOperator;
        $firstParam = $expression->firstParam;
        $secondParam = $expression->secondParam;
        if (
            $firstParam instanceof LiteralExpression
            && $firstParam->type == LiteralExpression::NULL
        ) {
            $comparisonOperator = $comparisonOperator == '=' ? 'IS' : 'IS NOT';
            $temp = $firstParam;
            $firstParam = $secondParam;
            $secondParam = $temp;
        }
        if (
            $secondParam instanceof LiteralExpression
            && $secondParam->type == LiteralExpression::NULL
        ) {
            $comparisonOperator = $comparisonOperator == '=' ? 'IS' : 'IS NOT';
        }

        return sprintf(
            "%s %s %s",
            $this->dumpExpression($firstParam),
            $comparisonOperator,
            $this->dumpExpression($secondParam)
        );
    }

    /**
     * @param ParamFieldExpression $expression
     * @return string
     */
    private function dumpParamField(ParamFieldExpression $expression)
    {
        return sprintf("%s.%s", $expression->paramName, $expression->getFinalFieldName());
    }

    /**
     * @param ArrayExpression $expression
     * @return string
     */
    private function dumpArrayExpression(ArrayExpression $expression)
    {
        return sprintf(
            "[%s]",
            implode(", ", array_map(function (LiteralExpression $literal) {
                return $this->dumpExpression($literal);
            }, $expression->literals))
        );
    }

    /**
     * @param VariableExpression $expression
     * @return string
     * @throws DumperException
     */
    private function dumpVariableExpression(VariableExpression $expression)
    {
        if (!isset($this->result->variables[$expression->name])) {
            throw new DumperException(sprintf("Variable %s is undefined", $expression->name));
        }
        $value = $this->result->variables[$expression->name];


        if ($value === null) {
            return "NULL";
        }

        return sprintf(":%s", $expression->name);
    }

    /**
     * @param LiteralExpression $expression
     * @return string
     */
    private function dumpLiteral(LiteralExpression $expression)
    {
        if ($expression->value === null) {
            return "NULL";
        }

        $varName = sprintf("auto_var_%s", $this->autoVar++);
        $this->result->variables[$varName] = $expression->value;

        return sprintf(":%s", $varName);
    }

    /**
     * @param FunctionExpression $expression
     * @return string
     * @throws DumperException
     */
    private function dumpFunctionExpression($expression)
    {
        switch (get_class($expression)) {
            case InArrayFunction::class:
                /** @var InArrayFunction $expression */
                return $this->dumpFunctionInArray($expression);
            default:
                throw new DumperException("Unknown function");
        }
    }

    /**
     * @param InArrayFunction $expression
     * @return string
     */
    private function dumpFunctionInArray(InArrayFunction $expression)
    {
        if (is_array($expression->firstParam)) {
            $haystack = $expression->firstParam;
            $needle = $expression->secondParam;
        } else {
            $haystack = $expression->secondParam;
            $needle = $expression->firstParam;
        }

        /** @var ArrayExpression $haystack */
        $hayStackExpression = implode(", ", array_map(function (LiteralExpression $literal) {
            return $this->dumpExpression($literal);
        }, $haystack->literals));

        return sprintf(
            "%s %sIN (%s)",
            $this->dumpExpression($needle),
            $expression->not ? "NOT " : "",
            $hayStackExpression
        );
    }
}