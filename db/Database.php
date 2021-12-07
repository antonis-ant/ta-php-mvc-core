<?php


namespace tonyanant\phpmvc\db;


use tonyanant\phpmvc\Application;

class Database
{
    public \PDO $pdo;

    /**
     * Database constructor.
     * @param array $config
     */
    public function __construct(array $config) {
        // Get db credentials.
        $dsn = $config['dsn'] ?? '';
        $user = $config['user'] ?? '';
        $password = $config['password'] ?? '';
        // Establish pdo connection
        $this->pdo = new \PDO($dsn, $user, $password);
        // Make sure in case of error, pdo throws exception.
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    public function applyMigrations() {
        // 1. Create migrations table (if it doesn't exist) & get already applied migrations.
        $this->createMigrationsTable();
        $appliedMigrations = $this->getAppliedMigrations();

        // 2. Gather all migration files & init new migrations array.
        $files = scandir(Application::$ROOT_DIR . '/migrations');
        $newMigrations = [];

        // 3. Find the migrations that do not yet exist in database (not applied yet) & apply them.
        $toApplyMigrations = array_diff($files, $appliedMigrations);
        foreach ($toApplyMigrations as $migration) {
            // Ignore these two directories.
            if ($migration === '.' || $migration === '..') {
                continue;
            }
            // Get migration class name & create migration instance.
            require_once Application::$ROOT_DIR . '/migrations/' . $migration;
            $className = pathinfo($migration, PATHINFO_FILENAME);
            $mig_instance = new $className();
            $this->log("Applying migration $migration");
            $mig_instance->up();
            $this->log("Applied migration $migration");
            $newMigrations[] = $migration;
        }

        // 4. If there are new migrations, save them to the database.
        if (!empty($newMigrations)) {
            $this->saveMigrations($newMigrations);
        } else {
            $this->log("All migrations are applied.");
        }
    }

    public function createMigrationsTable() {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
        )   ENGINE=INNODB;");
    }

    /**
     * Fetches applied migrations names from migrations table.
     * @return array: Applied migrations.
     */
    public function getAppliedMigrations() {
        $statement = $this->pdo->prepare("SELECT migration FROM migrations");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function saveMigrations(array $migrations) {
        // Use php map arrow function to properly format data for the query.
        $str = implode(",", array_map(fn($m) => "('$m')", $migrations));
        $statement = $this->pdo->prepare("INSERT INTO migrations (migration) VALUES $str");
        $statement->execute();
    }

    public function prepare($sql) {
        return $this->pdo->prepare($sql);
    }

    protected function log($message) {
        echo '[' . date('Y-m-d H:i:s') . '] - ' . $message . PHP_EOL;
    }
}