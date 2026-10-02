<?php
declare(strict_types=1);

namespace PokeTracker;

class App
{
    public \PDO $db;
    public Auth $auth;
    public Pokedex $pokedex;
    public Tracker $tracker;
    private string $rootDir;

    public function __construct(string $rootDir)
    {
        $this->rootDir = $rootDir;
        $dataDir = $rootDir . '/data';
        if (!is_dir($dataDir)) {
            @mkdir($dataDir, 0o750, true);
        }

        $this->db = new \PDO('sqlite:' . $dataDir . '/tracker.db');
        $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

        $this->migrate();

        $this->auth = new Auth($this->db);
        $this->pokedex = new Pokedex($this->db);
        $this->tracker = new Tracker($this->db);
    }

    private function migrate(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                password_hash TEXT NOT NULL,
                role TEXT DEFAULT 'user',
                created_at INTEGER NOT NULL
            )
        ");
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS pokemon (
                id INTEGER NOT NULL,
                name TEXT NOT NULL,
                name_display TEXT NOT NULL,
                generation TEXT NOT NULL,
                sprite TEXT NOT NULL,
                sprite_shiny TEXT NOT NULL,
                PRIMARY KEY (id, name)
            )
        ");
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS tracker (
                pokemon_id INTEGER PRIMARY KEY,
                status TEXT NOT NULL DEFAULT 'not-found',
                notes TEXT,
                updated_at INTEGER NOT NULL
            )
        ");
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS settings (
                key TEXT PRIMARY KEY,
                value TEXT NOT NULL
            )
        ");
    }

    public function seedIfNeeded(): void
    {
        $seedFile = __DIR__ . '/../seeds/pokedex.json';
        if (!file_exists($seedFile)) {
            throw new \RuntimeException('Seed file not found: ' . $seedFile);
        }
        $seedHash = md5_file($seedFile);

        $stmt = $this->db->prepare("SELECT value FROM settings WHERE key = 'seed_version'");
        $stmt->execute();
        $currentVersion = $stmt->fetchColumn();

        if ($currentVersion !== $seedHash) {
            $this->pokedex->seed();
            $this->db->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES ('seed_version', ?)")
                ->execute([$seedHash]);
        }
    }
}
