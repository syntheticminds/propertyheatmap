<?php

namespace App\Providers;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Builder::macro('withoutIndexes', function (callable $callback) {
            $indexes = $this->connection->getSchemaBuilder()->getIndexes($this->from);

            $blueprint = new Blueprint($this->from);

            foreach ($indexes as $index) {
                if (!$index['primary']) {
                    $blueprint->dropIndex($index['name']);
                }
            }

            $blueprint->build($this->connection, $this->connection->getSchemaGrammar());

            $callback($this);

            $blueprint = new Blueprint($this->from);

            foreach ($indexes as $index) {
                if (!$index['primary']) {
                    $blueprint->index($index['columns'], $index['name']);
                }
            }

            $blueprint->build($this->connection, $this->connection->getSchemaGrammar());
        });
    }
}
