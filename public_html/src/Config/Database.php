<?php

declare(strict_types=1);

namespace App\Config;

class Database
{
    private static ?\PDO $instance = null;

    public static function getInstance(): \PDO
    {
        if (self::$instance === null) {
            $host     = self::requerido('DB_HOST');
            $dbname   = self::requerido('DB_NAME');
            $user     = self::requerido('DB_USER');
            $password = self::requerido('DB_PASSWORD');
            $charset  = 'utf8mb4';

            $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";

            $options = [
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new \PDO($dsn, $user, $password, $options);
            } catch (\PDOException $e) {
                http_response_code(500);
                die(json_encode([
                    'success' => false,
                    'message' => 'Erro de conexão com o banco de dados.',
                ]));
            }
        }

        return self::$instance;
    }

    /**
     * Le uma variavel obrigatoria do .env. Sem valor padrao escondido no codigo:
     * se faltar, registra no log e interrompe com mensagem clara.
     */
    private static function requerido(string $chave): string
    {
        $valor = $_ENV[$chave] ?? '';
        if ($valor === '') {
            error_log("[TdOkei] Variavel de ambiente obrigatoria nao definida: {$chave}");
            http_response_code(500);
            $debug = filter_var($_ENV['APP_DEBUG'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
            die(json_encode([
                'success' => false,
                'message' => $debug
                    ? "Configuracao ausente: defina {$chave} no arquivo .env."
                    : 'Erro de configuracao do servidor.',
            ]));
        }
        return $valor;
    }

    private function __construct() {}
    private function __clone() {}
}
