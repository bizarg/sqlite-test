<?php

namespace Bizarg\Test;

use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Http\Response;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\TestCase;

/**
 * Class TestAbstract
 * @package Bizarg\TypeHelper
 */
abstract class TestAbstract extends TestCase
{
    use CreateApplication;

    /**
     * @var string|null
     */
    protected ?string $token = null;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->refreshTestDatabase();

        $this->withoutMiddleware(ThrottleRequests::class);
    }

    /**
     * @return void
     */
    private function truncateTables(): void
    {
        $exclusions = ['migrations'];

        $tables = $this->getTables();

        foreach ($tables as $table) {
            $table = (array) $table;

            if (!in_array($table[key($table)], $exclusions)) {
                DB::table($table[key($table)])->truncate();
            }
        }
    }

    /**
     * @return array
     */
    private function getTables(): array
    {
        if (env('DB_CONNECTION') == 'sqlite') {
            return DB::select("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name;");
        } else {
            return DB::select('SHOW TABLES');
        }
    }

    /**
     * @return void
     */
    protected function refreshTestDatabase(): void
    {
        if (config('database.default') !== 'sqlite') {
            if (! RefreshDatabaseState::$migrated) {
                info('test migrate:fresh');
                $this->artisan('migrate:fresh');

                RefreshDatabaseState::$migrated = true;
            }

            $this->truncateTables();
            $this->artisan('db:seed');

            return;
        }

        $this->makeSqlite();
    }

    /**
     * @return void
     */
    protected function makeSqlite(): void
    {
        if (!is_dir(storage_path('/db'))) {
            mkdir(storage_path('/db'));
            chmod(storage_path('/db'), 0777);
        }

        if (!file_exists('./storage/db/testdb-example.sqlite')) {
            $handler = fopen(storage_path('db/testdb.sqlite'), 'w');
            fclose($handler);

            chmod('./storage/db/testdb.sqlite', 0777);

            $this->artisan('migrate');
            $this->artisan('db:seed');
            $this->artisan('passport:install');

            copy('./storage/db/testdb.sqlite', './storage/db/testdb-example.sqlite');
            chmod('./storage/db/testdb-example.sqlite', 0777);
        }

        copy('./storage/db/testdb-example.sqlite', './storage/db/testdb.sqlite');
    }

    /**
     * Sign in
     * @param string $email
     * @param string $password
     * @return array
     */
    protected function signIn($email = 'test@test.com', $password = 'testpass'): array
    {
        $res = $this->json('post', route('api.auth.login'), [
            'email' => $email,
            'password' => $password
        ]);

        $result = json_decode($res->content(), true)['data']['attributes'];

        $this->token = $result['accessToken'];

        return $result;
    }

    /**
     * @param $name
     * @return array
     */
    protected function errorRequiredField($name): array
    {
        $lowerCaseName = strtolower(preg_replace('/([A-Z]+)/', ' $1', $name));
        return  [
            'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
            'detail' => "The {$lowerCaseName} field is required.",
            'source' => [
                'parameter' => $name
            ],
        ];
    }

    /**
     * @return array
     */
    protected function errorPageNotFound(): array
    {
        return [
            'detail' => 'Page not found.',
            'status' => Response::HTTP_NOT_FOUND
        ];
    }

    /**
     * @return array
     */
    protected function headers(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->token
        ];
    }
}
