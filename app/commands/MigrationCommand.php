<?php
/**
 * Command: Migration
 *
 * Auto-discovered by the LavaLust CLI.
 * No registration needed — just drop this file in app/commands/.
 */
class MigrationCommand
{
    /**
     * The CLI command name.
     * Usage: php lava migration
     */
    public static $command = 'migration';

    /** Short description shown in php lava help */
    public static $description = 'Run LavaLust database migrations';

    /**
     * Argument/flag descriptions shown in help.
     *
     * Example:
     *   public static $arguments = [
     *       'name'        => 'A positional argument',
     *       '[--flag=<v>]' => 'An optional flag',
     *   ];
     */
    public static $arguments = [
        '[action]' => 'Action: run, create-migration, rollback, rollback-all, refresh, status',
        '[name]' => 'Migration name used with create-migration',
    ];

    private static $route_map = [
        'run' => 'migrate',
        'create-migration' => 'create-migration',
        'rollback' => 'rollback',
        'rollback-all' => 'rollback-all',
        'refresh' => 'refresh',
        'status' => 'status',
    ];

    public function handle($action = null, array $flags = [], $name = null)
    {
        $action = $action ?? 'run';
        if (!isset(self::$route_map[$action])) {
            fwrite(STDERR, "Unknown migration action: {$action}\n");
            fwrite(STDERR, 'Available actions: ' . implode(', ', array_keys(self::$route_map)) . PHP_EOL);
            exit(1);
        }

        if ($action === 'create-migration') {
            if (!is_string($name) || !preg_match('/^[a-z][a-z0-9_]*$/', $name)) {
                fwrite(STDERR, "A valid migration name is required, for example: create_products_table\n");
                exit(1);
            }
            $route = 'create-migration/' . $name;
        } else {
            $route = self::$route_map[$action];
        }

        if (in_array($action, ['rollback', 'rollback-all', 'refresh'], true)
            && strtolower((string) (getenv('APP_ENV') ?: 'development')) === 'production') {
            fwrite(STDERR, "Destructive migration actions are disabled in production.\n");
            exit(1);
        }

        $index = PUBLIC_DIR . 'index.php';
        if (!file_exists($index)) {
            fwrite(STDERR, "Front controller not found: {$index}\n");
            exit(1);
        }

        $command = sprintf('php %s %s', escapeshellarg($index), escapeshellarg($route));
        passthru($command, $exit_code);
        if ($exit_code !== 0) {
            exit($exit_code);
        }
    }
}