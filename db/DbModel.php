<?php


namespace tonyanant\phpmvc\db;


use tonyanant\phpmvc\Application;
use tonyanant\phpmvc\Model;

abstract class DbModel extends Model
{
    abstract public function tableName(): string;

    abstract public function attributes(): array;
    abstract public function primaryKey(): string;

    public function save() {
        $tableName = $this->tableName();
        $attributes = $this->attributes();

        // Properly format parameters to work inside the query.
        $params = array_map(fn($attr) => ":$attr", $attributes);
        $statement = self::prepare("INSERT INTO $tableName (".implode(',', $attributes).") 
                VALUES (".implode(',', $params).")");

        // Bind each attribute to its value & execute query.
        foreach ($attributes as $attribute) {
            $statement->bindValue(":$attribute", $this->{$attribute});
        }
        $statement->execute();

        return true;
    }

    public function findOne($where) {
        $tableName = static::tableName();
        $attributes = array_keys($where);
        $sql = implode("AND ", array_map(fn($attr)=>"$attr = :$attr", $attributes));
        $statement = self::prepare("SELECT * FROM $tableName WHERE $sql");
        foreach ($where as $key => $item) {
            $statement->bindValue(":$key", $item);
        }
        $statement->execute();

        // Use "static::class" to return object as instance model class.
        return $statement->fetchObject(static::class);
    }


    public static function prepare($sql) {
        return Application::$app->db->pdo->prepare($sql);
    }
}