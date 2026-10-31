<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Config\Database;
use PDO;

class ConfiguracaoRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /** @return array<string,string|null> */
    public function todas(): array
    {
        $r = $this->db->query("SELECT chave, valor FROM configuracoes")->fetchAll(PDO::FETCH_KEY_PAIR);
        return $r ?: [];
    }

    public function obter(string $chave): ?string
    {
        $stmt = $this->db->prepare("SELECT valor FROM configuracoes WHERE chave = :c");
        $stmt->execute([':c' => $chave]);
        $v = $stmt->fetchColumn();
        return $v === false ? null : ($v === null ? null : (string) $v);
    }

    public function salvar(string $chave, ?string $valor): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO configuracoes (chave, valor) VALUES (:c, :v)
             ON DUPLICATE KEY UPDATE valor = VALUES(valor)"
        );
        $stmt->execute([':c' => $chave, ':v' => $valor]);
    }
}
