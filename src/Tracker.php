<?php
declare(strict_types=1);

namespace PokeTracker;

class Tracker
{
    private \PDO $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    public function get(int $pokemonId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM tracker WHERE pokemon_id = ?");
        $stmt->execute([$pokemonId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function all(): array
    {
        $stmt = $this->db->query("SELECT * FROM tracker");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function setStatus(int $pokemonId, string $status, ?string $notes = null): void
    {
        $valid = ['caught', 'seen', 'not-found'];
        if (!in_array($status, $valid, true)) {
            throw new \InvalidArgumentException('Invalid status');
        }
        $existing = $this->get($pokemonId);
        if ($existing) {
            $stmt = $this->db->prepare("UPDATE tracker SET status = ?, notes = ?, updated_at = ? WHERE pokemon_id = ?");
            $stmt->execute([$status, $notes, time(), $pokemonId]);
        } else {
            $stmt = $this->db->prepare("INSERT INTO tracker (pokemon_id, status, notes, updated_at) VALUES (?, ?, ?, ?)");
            $stmt->execute([$pokemonId, $status, $notes, time()]);
        }
    }

    public function clear(int $pokemonId): void
    {
        $stmt = $this->db->prepare("DELETE FROM tracker WHERE pokemon_id = ?");
        $stmt->execute([$pokemonId]);
    }

    public function stats(): array
    {
        $total = (int) $this->db->query("SELECT COUNT(*) FROM pokemon")->fetchColumn();
        $caught = (int) $this->db->query("SELECT COUNT(*) FROM tracker WHERE status = 'caught'")->fetchColumn();
        $seen = (int) $this->db->query("SELECT COUNT(*) FROM tracker WHERE status = 'seen'")->fetchColumn();
        $notFound = (int) $this->db->query("SELECT COUNT(*) FROM tracker WHERE status = 'not-found'")->fetchColumn();
        return [
            'total' => $total,
            'caught' => $caught,
            'seen' => $seen,
            'not-found' => $notFound,
            'remaining' => $total - $caught,
            'percent' => $total > 0 ? round($caught / $total * 100, 1) : 0,
        ];
    }

    public function statsByGeneration(): array
    {
        $stmt = $this->db->query("
            SELECT p.generation,
                   COUNT(*) as total,
                   SUM(CASE WHEN t.status = 'caught' THEN 1 ELSE 0 END) as caught,
                   SUM(CASE WHEN t.status = 'seen' THEN 1 ELSE 0 END) as seen
            FROM pokemon p
            LEFT JOIN tracker t ON p.id = t.pokemon_id
            GROUP BY p.generation
            ORDER BY p.generation
        ");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
