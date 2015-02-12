<?php

namespace ClosureIt;

use Closure;
use ClosureIt\Expressions\ArrayExpression;
use ClosureIt\Expressions\ComparisonExpression;
use ClosureIt\Expressions\ConditionalExpression;
use ClosureIt\Expressions\ConditionalPrimaryExpression;
use ClosureIt\Expressions\LiteralExpression;
use ClosureIt\Expressions\ParamExpression;
use ClosureIt\Expressions\ParamFieldExpression;
use ClosureIt\Expressions\VariableExpression;
use ClosureIt\Functions\FunctionExpression;
use ClosureIt\Functions\InArrayFunction;

class Parser
{

    /**
     * @var Lexer
     */
    private $lexer;

    /**
     * @var Analyzer
     */
    private $analyzer;

    private $functions = [
        'in_array' => InArrayFunction::class
    ];

    function __construct(Closure $closure)
    {
        $this->analyzer = new Analyzer($closure);
        $this->lexer = new Lexer($this->analyzer->getTokens());
    }

    /**
     * @return Result
     */
    public function parse()
    {
        $this->lexer->moveNext();
        $expression = $this->parseConditionalExpression();

        return new Result(
            $expression,
            $this->analyzer->getParams(),
            $this->analyzer->getVariables()
        );
    }

    public function parseConditionalExpression($not = false)
    {
        $conditionalPrimaries = [];
        $conditionalPrimaries[] = $this->parseConditionalPrimary();

        if ($this->lexer->isNextToken(T_BOOLEAN_AND)) {
            $tokenType = T_BOOLEAN_AND;
        } else {
            if ($this->lexer->isNextToken(T_BOOLEAN_OR)) {
                $tokenType = T_BOOLEAN_OR;
            } else if ($not) {
                $tokenType = false;
            } else {
                return $conditionalPrimaries[0];
            }
        }

        while ($tokenType && $this->lexer->isNextToken($tokenType)) {
            $this->match($tokenType);
            $conditionalPrimaries[] = $this->parseConditionalPrimary();
        }

        return new ConditionalExpression(
            $conditionalPrimaries,
            $tokenType == T_BOOLEAN_AND
                ? ConditionalExpression::D_AND : ConditionalExpression::D_OR,
            $not
        );
    }

    public function parseConditionalPrimary()
    {
        $condPrimary = new ConditionalPrimaryExpression();
        if ($this->isSimpleConditionalExpression(true)) {
            $condPrimary->simpleConditionalExpression = $this->parseSimpleConditionalExpression();

            return $condPrimary;
        }

        $not = false;
        if ($this->lexer->isNextToken(Lexer::T_NOT)) {
            $this->match(Lexer::T_NOT);
            $not = true;
        }
        $this->match(Lexer::T_OPEN_PARENTHESIS);
        $condPrimary->conditionalExpression = $this->parseConditionalExpression($not);
        $this->match(Lexer::T_CLOSE_PARENTHESIS);

        return $condPrimary;
    }

    public function parseSimpleConditionalExpression()
    {
        if ($this->isFunction(true)) {
            $not = false;
            if ($this->lexer->isNextToken(Lexer::T_NOT)) {
                $this->match(Lexer::T_NOT);
                $not = true;
            }

            return $this->parseFunction($not);
        } else {
            return $this->parseComparisonExpression();
        }
    }

    public function parseComparisonExpression()
    {
        $comparisonExpression = new ComparisonExpression();
        $comparisonExpression->firstParam = $this->parseParamExpression();
        $comparisonExpression->comparisonOperator = $this->parseComparisonOperator();
        $comparisonExpression->secondParam = $this->parseParamExpression();

        return $comparisonExpression;
    }

    /**
     * @return ParamExpression
     */
    public function parseParamExpression()
    {
        if ($this->lexer->isNextToken(T_VARIABLE)) {
            if ($this->lexer->peek()['type'] == T_OBJECT_OPERATOR) {
                return $this->parseParamField();
            } else {
                return $this->parseVariable();
            }
        } else {
            if ($this->isArray()) {
                return $this->parseArray();
            } else {
                return $this->parseLiteral();
            }
        }
    }

    /**
     * @return ParamFieldExpression
     */
    public function parseParamField()
    {
        $this->match(T_VARIABLE);
        $paramName = substr($this->lexer->token['value'], 1);
        $this->match(T_OBJECT_OPERATOR);
        $this->match(T_STRING);
        $fieldName = $this->lexer->token['value'];

        $isFunction = false;
        if ($this->lexer->isNextToken(Lexer::T_OPEN_PARENTHESIS)) {
            $this->match(Lexer::T_OPEN_PARENTHESIS);
            $this->match(Lexer::T_CLOSE_PARENTHESIS);
            $isFunction = true;
        }

        return new ParamFieldExpression($paramName, $fieldName, $isFunction);
    }

    /**
     * @return VariableExpression
     */
    public function parseVariable()
    {
        $this->match(T_VARIABLE);
        $variableName = substr($this->lexer->token['value'], 1);

        return new VariableExpression($variableName);
    }

    /**
     * @return LiteralExpression|null
     * @throws ParserException
     */
    public function parseLiteral()
    {
        $type = $this->lexer->lookahead['type'];
        $value = $this->lexer->lookahead['value'];

        switch ($type) {
            case T_CONSTANT_ENCAPSED_STRING:
                $this->match(T_CONSTANT_ENCAPSED_STRING);

                //substr is here because string has quotes and we don't need them
                return new LiteralExpression(LiteralExpression::STRING, substr($value, 1, -1));
            case T_LNUMBER:
            case T_DNUMBER:
                $this->match(
                    $this->lexer->isNextToken(T_LNUMBER) ? T_LNUMBER : T_DNUMBER
                );

                return new LiteralExpression(LiteralExpression::NUMERIC, (float)$value);
            case T_STRING:
                switch ($value) {
                    case 'true':
                    case 'false':
                        $this->match(T_STRING);

                        return new LiteralExpression(LiteralExpression::BOOLEAN, (bool)$value);
                    case 'null':
                        $this->match(T_STRING);

                        return new LiteralExpression(LiteralExpression::NULL, null);
                }

        }
        $this->syntaxError('Literal');

        return null;
    }

    /**
     * @return string
     * @throws ParserException
     */
    public function parseComparisonOperator()
    {
        $lookaheadType = $this->lexer->lookahead['type'];
        switch ($lookaheadType) {
            case T_IS_EQUAL:
            case T_IS_IDENTICAL:
                $this->match($lookaheadType == T_IS_EQUAL ? T_IS_EQUAL : T_IS_IDENTICAL);

                return '=';
            case Lexer::T_LOWER_THAN:
                $this->match(Lexer::T_LOWER_THAN);

                return '<';
            case Lexer::T_GREATER_THAN:
                $this->match(Lexer::T_GREATER_THAN);

                return '>';
            case T_IS_SMALLER_OR_EQUAL:
                $this->match(T_IS_SMALLER_OR_EQUAL);

                return '<=';
            case T_IS_GREATER_OR_EQUAL:
                $this->match(T_IS_GREATER_OR_EQUAL);

                return '>=';
            case T_IS_NOT_EQUAL:
            case T_IS_NOT_IDENTICAL:
                $this->match($lookaheadType == T_IS_NOT_EQUAL ? T_IS_NOT_EQUAL : T_IS_NOT_IDENTICAL);

                return '<>';
            default:
                $this->syntaxError('==, ===, <, <=, >, >=, !=, !==');

                return null;
        }
    }

    /**
     * @param bool $not
     * @return FunctionExpression
     * @throws ParserException
     */
    private function parseFunction($not = false)
    {
        $functionName = $this->lexer->lookahead['value'];

        $function = $this->createFunctionObjectByName($functionName);
        $function->not = $not;

        $this->lexer->moveNext();

        $function->parse($this);

        return $function;
    }

    private function parseArray()
    {
        $lookaheadType = $this->lexer->lookahead['type'];

        if ($lookaheadType == Lexer::T_OPEN_BRACKET) {
            $this->match(Lexer::T_OPEN_BRACKET);
            $newStyle = true;
        } else {
            $this->match(T_STRING);
            $this->match(Lexer::T_OPEN_PARENTHESIS);
            $newStyle = false;
        }
        $closingBracket = $newStyle
            ? Lexer::T_CLOSE_BRACKET : Lexer::T_CLOSE_PARENTHESIS;

        $literals = [];
        while (!$this->lexer->isNextToken($closingBracket)) {
            $literals[] = $this->parseLiteral();
            if ($this->lexer->isNextToken(Lexer::T_COMMA)) {
                $this->match(Lexer::T_COMMA);
            }
        }
        $this->match($closingBracket);

        return new ArrayExpression($literals);
    }

    public function isSimpleConditionalExpression($possiblyWithNot = false)
    {
        $lookahead = $this->lexer->lookahead;
        $peek = $this->lexer->glimpse();

        $isSimple = $lookahead['type'] !== Lexer::T_OPEN_PARENTHESIS;

        if ($possiblyWithNot && $lookahead['type'] === Lexer::T_NOT) {
            $isSimple = $peek['type'] !== Lexer::T_OPEN_PARENTHESIS;
        }

        return $isSimple;
    }

    public function isFunction($possiblyWithNot = false)
    {
        $lookahead = $this->lexer->lookahead;
        $peek = $this->lexer->peek();
        $nextPeek = $this->lexer->peek();
        $this->lexer->resetPeek();

        $isFunction = isset($this->functions[$lookahead['value']])
            && $peek['type'] === Lexer::T_OPEN_PARENTHESIS;

        if ($possiblyWithNot && $lookahead['type'] === Lexer::T_NOT) {
            $isFunction = isset($this->functions[$peek['value']])
                && $nextPeek['type'] === Lexer::T_OPEN_PARENTHESIS;
        }

        return $isFunction;
    }

    public function isArray()
    {
        $lookaheadType = $this->lexer->lookahead['type'];
        $peekType = $this->lexer->glimpse()['type'];

        return (
            $lookaheadType == Lexer::T_OPEN_BRACKET
            || (
                $lookaheadType == T_STRING && $this->lexer->lookahead['value'] == 'array'
                && $peekType == Lexer::T_OPEN_PARENTHESIS
            )
        );
    }

    public function match($token)
    {
        if (!$this->lexer->isNextToken($token)) {
            $this->syntaxError($this->lexer->getLiteral($token));
        }
        $this->lexer->moveNext();
    }

    public function syntaxError($expected = '', $token = null)
    {
        if ($token === null) {
            $token = $this->lexer->lookahead;
        }
        $message = "";
        $message .= ($expected !== '') ? "Expected {$expected}, got " : 'Unexpected ';
        $message .= ($this->lexer->lookahead === null) ? 'end of string.' : "'{$token['value']}'";
        throw new ParserException($message);
    }

    /**
     * @param  $functionName
     * @return FunctionExpression
     * @throws ParserException
     */
    private function createFunctionObjectByName($functionName)
    {
        if (!isset($this->functions[$functionName])) {
            throw new ParserException(sprintf("Unknown function '%s'", $functionName));
        }

        $functionClassName = $this->functions[$functionName];

        return new $functionClassName();
    }
}