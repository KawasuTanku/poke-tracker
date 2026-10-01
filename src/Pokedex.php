<?php
declare(strict_types=1);

namespace PokeTracker;

class Pokedex
{
    private \PDO $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    public function seed(): void
    {
        $seedFile = __DIR__ . '/../seeds/pokedex.json';
        if (!file_exists($seedFile)) {
            throw new \RuntimeException('Seed file not found: ' . $seedFile);
        }
        $data = json_decode(file_get_contents($seedFile), true);
        if (!is_array($data)) {
            throw new \RuntimeException('Invalid seed data');
        }

        $this->db->beginTransaction();
        try {
            $this->db->exec("DELETE FROM pokemon");
            $stmt = $this->db->prepare(
                "INSERT INTO pokemon (id, name, name_display, generation, sprite, sprite_shiny) VALUES (?, ?, ?, ?, ?, ?)"
            );
            foreach ($data as $p) {
                $stmt->execute([
                    $p['id'], $p['name'], $p['name_display'], $p['generation'],
                    $p['sprite'], $p['sprite_shiny'],
                ]);
            }
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function count(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM pokemon")->fetchColumn();
    }

    public function all(): array
    {
        $stmt = $this->db->query("SELECT * FROM pokemon ORDER BY id, name");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM pokemon WHERE id = ? ORDER BY name");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function byGeneration(string $gen): array
    {
        $stmt = $this->db->prepare("SELECT * FROM pokemon WHERE generation = ? ORDER BY id, name");
        $stmt->execute([$gen]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function search(string $q): array
    {
        $stmt = $this->db->prepare("SELECT * FROM pokemon WHERE name LIKE ? OR name_display LIKE ? ORDER BY id, name");
        $like = '%' . $q . '%';
        $stmt->execute([$like, $like]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function generations(): array
    {
        $stmt = $this->db->query("SELECT DISTINCT generation FROM pokemon ORDER BY generation");
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }
}
