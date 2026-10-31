<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Serviço de cache com Redis (principal) e arquivo (fallback).
 *
 * Usado para cachear dados pesados como relatórios e estatísticas,
 * que fazem múltiplas queries ao banco e mudam raramente.
 */
class CacheService
{
    private static ?\Redis $redis = null;
    private static bool    $redisDisponivel = false;

    // TTLs padrão (segundos)
    public const TTL_RELATORIOS    = 300;   // 5 minutos
    public const TTL_DASHBOARD     = 120;   // 2 minutos
    public const TTL_NOTIFICACOES  = 30;    // 30 segundos
    public const TTL_LISTAS        = 600;   // 10 minutos (produtos, unidades, escolas)

    // ----------------------------------------------------------------
    // API pública
    // ----------------------------------------------------------------

    /**
     * Busca um valor do cache. Retorna null se não existir ou expirado.
     */
    public static function get(string $chave): mixed
    {
        $chave = self::prefixar($chave);

        if (self::redisOk()) {
            $valor = self::$redis->get($chave);
            if ($valor === false) return null;
            return unserialize($valor);
        }

        return self::fileGet($chave);
    }

    /**
     * Armazena um valor no cache com TTL em segundos.
     */
    public static function set(string $chave, mixed $valor, int $ttl = 300): void
    {
        $chave = self::prefixar($chave);

        if (self::redisOk()) {
            self::$redis->setEx($chave, $ttl, serialize($valor));
            return;
        }

        self::fileSet($chave, $valor, $ttl);
    }

    /**
     * Remove um item do cache.
     */
    public static function delete(string $chave): void
    {
        $chave = self::prefixar($chave);

        if (self::redisOk()) {
            self::$redis->del($chave);
            return;
        }

        self::fileDelete($chave);
    }

    /**
     * Remove todos os itens que comecem com um padrão.
     * Útil para invalidar grupos de cache (ex: ao salvar uma solicitação).
     */
    public static function deletePattern(string $padrao): void
    {
        $padrao = self::prefixar($padrao) . '*';

        if (self::redisOk()) {
            $chaves = self::$redis->keys($padrao);
            if (!empty($chaves)) {
                self::$redis->del($chaves);
            }
            return;
        }

        // File fallback: varre o diretório de cache
        $dir = self::fileCacheDir();
        foreach (glob($dir . '/*') as $arquivo) {
            $nome = basename($arquivo, '.cache');
            if (str_starts_with($nome, str_replace('*', '', $padrao))) {
                @unlink($arquivo);
            }
        }
    }

    /**
     * Cache com callback — busca do cache ou executa e armazena.
     *
     * Uso:
     *   $dados = CacheService::remember('relatorios:geral', 300, fn() => $repo->totaisGerais());
     */
    public static function remember(string $chave, int $ttl, callable $callback): mixed
    {
        $cached = self::get($chave);
        if ($cached !== null) {
            return $cached;
        }

        $valor = $callback();
        self::set($chave, $valor, $ttl);
        return $valor;
    }

    /**
     * Invalida cache relacionado a solicitações.
     * Chamado sempre que uma solicitação é criada ou tem status alterado.
     */
    public static function invalidarSolicitacoes(): void
    {
        self::deletePattern('relatorios:');
        self::deletePattern('dashboard:');
        self::deletePattern('historico:');
    }

    /**
     * Invalida cache de listas de cadastro.
     */
    public static function invalidarCadastros(): void
    {
        self::deletePattern('lista:');
    }

    // ----------------------------------------------------------------
    // Redis
    // ----------------------------------------------------------------

    private static function redisOk(): bool
    {
        if (self::$redisDisponivel) return true;
        if (!extension_loaded('redis')) return false;

        $host = $_ENV['REDIS_HOST']     ?? '127.0.0.1';
        $port = (int)($_ENV['REDIS_PORT'] ?? 6379);
        $pass = $_ENV['REDIS_PASSWORD'] ?? '';

        try {
            self::$redis = new \Redis();
            self::$redis->connect($host, $port, 1.5); // timeout de 1.5s
            if ($pass) self::$redis->auth($pass);
            self::$redis->ping();
            self::$redisDisponivel = true;
            return true;
        } catch (\Throwable $e) {
            self::$redis             = null;
            self::$redisDisponivel   = false;
            return false;
        }
    }

    private static function prefixar(string $chave): string
    {
        $prefix = $_ENV['REDIS_CACHE_PREFIX'] ?? 'tdokei_cache_';
        return $prefix . $chave;
    }

    // ----------------------------------------------------------------
    // Fallback em arquivo (quando Redis não está disponível)
    // ----------------------------------------------------------------

    private static function fileCacheDir(): string
    {
        $dir = sys_get_temp_dir() . '/fila_cache';
        if (!is_dir($dir)) {
            mkdir($dir, 0700, true);
        }
        return $dir;
    }

    private static function filePath(string $chave): string
    {
        return self::fileCacheDir() . '/' . md5($chave) . '.cache';
    }

    private static function fileGet(string $chave): mixed
    {
        $path = self::filePath($chave);
        if (!file_exists($path)) return null;

        $conteudo = file_get_contents($path);
        if ($conteudo === false) return null;

        $dados = unserialize($conteudo);
        if (!is_array($dados) || !isset($dados['expira'], $dados['valor'])) return null;

        if (time() > $dados['expira']) {
            @unlink($path);
            return null;
        }

        return $dados['valor'];
    }

    private static function fileSet(string $chave, mixed $valor, int $ttl): void
    {
        $path  = self::filePath($chave);
        $dados = serialize(['expira' => time() + $ttl, 'valor' => $valor]);
        file_put_contents($path, $dados, LOCK_EX);
    }

    private static function fileDelete(string $chave): void
    {
        @unlink(self::filePath($chave));
    }
}
