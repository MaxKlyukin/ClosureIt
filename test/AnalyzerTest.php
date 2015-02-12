<?php

namespace ClosureIt\Test;


use ClosureIt\Analyzer;
use ClosureIt\AnalyzerException;
use ClosureIt\Test\Entity\User;

class AnalyzerTest extends \PHPUnit_Framework_TestCase
{
    public function testSimpleVariable()
    {
        $closure = function () {
            return 1;
        };
        $analyzer = new Analyzer($closure);
        $this->assertEquals("1", $this->tokensToSource($analyzer->getTokens()));
    }

    public function testCondition()
    {
        $closure = function (User $user) {
            return $user->getName() == 'John';
        };

        $analyzer = new Analyzer($closure);
        $this->assertEquals("\$user->getName()=='John'", $this->tokensToSource($analyzer->getTokens()));
    }

    public function testReturnFunctionException()
    {
        $returnsClosure = function () {
            return function (User $user) {
                return $user->getName() == 'John';
            };
        };
        $closure = $returnsClosure();

        $analyzer = new Analyzer($closure);
        $this->assertEquals("\$user->getName()=='John'", $this->tokensToSource($analyzer->getTokens()));
    }

    public function testNestedInlineFunctionsException()
    {
        $returnsClosure = function () { return function (User $user) { return $user->getName() == 'John'; }; };
        $closure = $returnsClosure();

        $this->setExpectedException(
            AnalyzerException::class, "Nested inline functions are not supported"
        );

        new Analyzer($closure);
    }

    public function testComments()
    {
        $closure = function (User $user) {
            return $user->getName() == 'Stan' /*|| $user->getName() == 'John'*/
                ;
        };

        $analyzer = new Analyzer($closure);
        $this->assertEquals("\$user->getName()=='Stan'", $this->tokensToSource($analyzer->getTokens()));
    }

    public function testCommentsAndMustHaveReturnStatementException()
    {
        $closure = function (User $user) {
            //return $user->getName() == 'Stan';
        };

        $this->setExpectedException(
            AnalyzerException::class, "Closure must have valid return statement"
        );

        new Analyzer($closure);
    }

    public function testParams()
    {
        $closure = function (User $user) {
            return $user->getName() == 'Ivan';
        };

        $analyzer = new Analyzer($closure);
        $this->assertEquals(['user'], $analyzer->getParams());
    }

    public function testVariables()
    {
        $userName = 'John';
        $userAge = 34;
        $closure = function (User $user) use ($userName, $userAge) {
            return $user->getName() == $userName && $user->getAge() == $userAge;
        };

        $analyzer = new Analyzer($closure);
        $this->assertEquals(compact('userName', 'userAge'), $analyzer->getVariables());
    }


    private function tokensToSource($tokens)
    {
        return implode(array_map(function ($token) {
            return is_array($token) ? $token[1] : $token;
        }, $tokens));
    }
}
