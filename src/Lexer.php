<?php

namespace ClosureIt;

class Lexer
{

    const T_UNDEFINED = 'undefined';

    const T_SEMICOLON = 'semicolon';
    const T_COMMA = 'comma';

    const T_OPEN_PARENTHESIS = 'open_parenthesis';
    const T_CLOSE_PARENTHESIS = 'close_parenthesis';
    const T_OPEN_BRACKET = 'open_bracket';
    const T_CLOSE_BRACKET = 'close_bracket';

    const T_NOT = 'not';
//    const T_EQUALS = 'equals';
    const T_GREATER_THAN = 'greater_than';
//    const T_GREATER_OR_EQUALS_THAN = 'greater_or_equals_than';
    const T_LOWER_THAN = 'lower_than';
//    const T_LOWER_OR_EQUALS_THAN = 'lower_or_equals_than';

    private $tokens;

    private $position = 0;
    private $peek = 0;

    public $lookahead;
    public $token;

    private $tokenTypes = [
        ';' => self::T_SEMICOLON,
        ',' => self::T_COMMA,
        '(' => self::T_OPEN_PARENTHESIS,
        ')' => self::T_CLOSE_PARENTHESIS,
        '[' => self::T_OPEN_BRACKET,
        ']' => self::T_CLOSE_BRACKET,
        '!' => self::T_NOT,
//        '==' => self::T_EQUALS,
//        '===' => self::T_EQUALS,
        '>' => self::T_GREATER_THAN,
//        '>=' => self::T_GREATER_OR_EQUALS_THAN,
        '<' => self::T_LOWER_THAN,
//        '<=' => self::T_LOWER_OR_EQUALS_THAN,
    ];

    function __construct($tokens)
    {
        $this->tokens = $this->prepareTokens($tokens);
    }

    public function reset()
    {
        $this->lookahead = null;
        $this->token = null;
        $this->peek = 0;
        $this->position = 0;
    }

    public function resetPeek()
    {
        $this->peek = 0;
    }

    public function resetPosition($position = 0)
    {
        $this->position = $position;
    }

    public function isNextToken($token)
    {
        return null !== $this->lookahead && $this->lookahead['type'] === $token;
    }

    public function isNextTokenAny(array $tokens)
    {
        return null !== $this->lookahead && in_array($this->lookahead['type'], $tokens, true);
    }

    public function moveNext()
    {
        $this->peek = 0;
        $this->token = $this->lookahead;
        $this->lookahead = (isset($this->tokens[$this->position]))
            ? $this->tokens[$this->position++] : null;

        return $this->lookahead !== null;
    }

    public function skipUntil($type)
    {
        while ($this->lookahead !== null && $this->lookahead['type'] !== $type) {
            $this->moveNext();
        }
    }

    public function peek()
    {
        if (isset($this->tokens[$this->position + $this->peek])) {
            return $this->tokens[$this->position + $this->peek++];
        } else {
            return null;
        }
    }

    public function glimpse()
    {
        $peek = $this->peek();
        $this->peek = 0;

        return $peek;
    }

    private function prepareTokens($rawTokens)
    {
        return array_map(function ($rawToken) {
            if (is_array($rawToken)) {
                return [
                    'type' => $rawToken[0],
                    'value' => $rawToken[1],
                ];
            }

            $type = isset($this->tokenTypes[$rawToken])
                ? $this->tokenTypes[$rawToken] : self::T_UNDEFINED;

            return [
                'type' => $type,
                'value' => $rawToken,
            ];
        }, $rawTokens);
    }

    public function getLiteral($token)
    {
        return is_string($token)
            ? strtoupper("t_" . $token) : token_name($token);
    }
}