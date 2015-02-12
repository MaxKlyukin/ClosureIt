<?php

namespace ClosureIt\Test;

use ClosureIt\Dumpers\SqlDumper;
use ClosureIt\Parser;
use ClosureIt\Test\Entity\User;


class IntegrationTest extends \PHPUnit_Framework_TestCase
{
    public function testAnd()
    {
        $closure = function (User $user) {
            return $user->getName() == 'John' && $user->getAge() > 27;
        };
        $sql = "user.name = :auto_var_0 AND user.age > :auto_var_1";

        $parseResult = (new Parser($closure))->parse();
        $this->assertEquals($sql, (new SqlDumper($parseResult))->dump());
    }

    public function testInArrayFunction()
    {
        $closure = function (User $user) {
            return in_array($user->getLevel(), [4, 5]);
        };
        $sql = "user.level IN (:auto_var_0, :auto_var_1)";

        $parseResult = (new Parser($closure))->parse();

        $this->assertEquals($sql, (new SqlDumper($parseResult))->dump());
    }

    public function testParentheses()
    {
        $closure = function (User $user) {
            return ($user->getName() == 'John' && ($user->getAge() > 27));
        };
        $sql = "(user.name = :auto_var_0 AND (user.age > :auto_var_1))";

        $parseResult = (new Parser($closure))->parse();

        $this->assertEquals($sql, (new SqlDumper($parseResult))->dump());
    }

    public function testNotFunction()
    {
        $closure = function (User $user) {
            return !in_array($user->getLevel(), [4, 5]);
        };
        $sql = "user.level NOT IN (:auto_var_0, :auto_var_1)";

        $parseResult = (new Parser($closure))->parse();

        $this->assertEquals($sql, (new SqlDumper($parseResult))->dump());
    }

    public function testComplex()
    {
        $closure = function (User $user) {
            return
                ($user->getName() == 'John' && 27 < $user->getAge())
                || !in_array($user->getLevel(), [4, 5]);
        };
        $sql = "(user.name = :auto_var_0 AND :auto_var_1 < user.age) OR user.level NOT IN (:auto_var_2, :auto_var_3)";

        $parseResult = (new Parser($closure))->parse();

        $this->assertEquals($sql, (new SqlDumper($parseResult))->dump());
    }

    public function testWithVars()
    {
        $closure = function (User $user) {
            return $user->getName() == 'John' && 27 < $user->getAge() && !in_array($user->getLevel(), [4, 5]);
        };
        $sql = "user.name = :auto_var_0 AND :auto_var_1 < user.age AND user.level NOT IN (:auto_var_2, :auto_var_3)";
        $variables = [
            'auto_var_0' => "John",
            'auto_var_1' => 27,
            'auto_var_2' => 4,
            'auto_var_3' => 5,
        ];
        $parseResult = (new Parser($closure))->parse();

        $this->assertEquals($sql, (new SqlDumper($parseResult))->dump());
        $this->assertEquals($variables, $parseResult->variables);
    }

    public function testWithUseVars()
    {
        $userName = 'John';
        $userAge = 35;
        $closure = function (User $user) use($userName, $userAge) {
            return
                $user->getName() == $userName && $userAge < $user->getAge();

        };
        $sql = "user.name = :userName AND :userAge < user.age";
        $variables = [
            'userName' => $userName,
            'userAge' => $userAge,
        ];
        $parseResult = (new Parser($closure))->parse();

        $this->assertEquals($sql, (new SqlDumper($parseResult))->dump());
        $this->assertEquals($variables, $parseResult->variables);
    }

    public function testWithUseBothVars()
    {
        $userName = 'James';
        $closure = function (User $user) use($userName) {
            return
                $user->getName() == $userName && 35 < $user->getAge();

        };
        $sql = "user.name = :userName AND :auto_var_0 < user.age";
        $variables = [
            'userName' => $userName,
            'auto_var_0' => 35,
        ];
        $parseResult = (new Parser($closure))->parse();

        $this->assertEquals($sql, (new SqlDumper($parseResult))->dump());
        $this->assertEquals($variables, $parseResult->variables);
    }
}