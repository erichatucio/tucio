<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Controller: MigrationController
 *
 * Automatically generated via CLI.
 */
class MigrationController extends Controller {
    public function __construct()
    {
        parent::__construct();
    }

    public function before_action()
    {
        if (PHP_SAPI !== 'cli') {
            http_response_code(404);
            exit;
        }

        $this->migration = $this->call->library('migration');
    }

    public function create_migration($migration_class)
    {
        $this->migration->create_migration($migration_class);
    }

    public function migrate()
    {
        $this->migration->migrate();
    }

    public function rollback()
    {
        $this->assert_development_environment();
        $this->migration->rollback();
    }

    public function rollback_all()
    {
        $this->assert_development_environment();
        $this->migration->rollback_all();
    }

    public function refresh()
    {
        $this->assert_development_environment();
        $this->migration->refresh();
    }

    public function status()
    {
        $this->migration->status();
    }

    private function assert_development_environment()
    {
        if (strtolower((string) config_item('environment')) === 'production') {
            fwrite(STDERR, "Destructive migration actions are disabled in production.\n");
            exit(1);
        }
    }
}