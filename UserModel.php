<?php


namespace tonyanant\phpmvc;


use tonyanant\phpmvc\db\DbModel;

abstract class UserModel extends DbModel
{
    abstract public function getDisplayName(): string;
}