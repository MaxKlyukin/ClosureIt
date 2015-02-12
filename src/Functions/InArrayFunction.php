<?php

namespace ClosureIt\Functions;

use ClosureIt\Expressions\ParamExpression;
use ClosureIt\Lexer;
use ClosureIt\Parser;

class InArrayFunction extends FunctionExpression
{
    /**
     * @var ParamExpression
     */
    public $firstParam;

    /**
     * @var ParamExpression
     */
    public $secondParam;

    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->firstParam = $parser->parseParamExpression();
        $parser->match(Lexer::T_COMMA);
        $this->secondParam = $parser->parseParamExpression();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}