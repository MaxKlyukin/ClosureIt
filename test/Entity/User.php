<?php

namespace ClosureIt\Test\Entity;

class User
{

    private $name;
    private $age;
    private $level;

    public function getName()
    {
        return $this->name;
    }

    public function getAge()
    {
        return $this->age;
    }

    public function getLevel()
    {
        return $this->level;
    }
}