<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contact_groups', function (Blueprint $table) {
            if (!Schema::hasColumn('contact_groups', 'is_default')) {
                $table->boolean('is_default')->default(false)->after('user_id');
            }

            if (!Schema::hasColumn('contact_groups', 'color')) {
                $table->string('color', 7)->nullable()->after('description');
            }

            if (!Schema::hasColumn('contact_groups', 'beem_address_book_id')) {
                $table->string('beem_address_book_id')->nullable()->after('color');
            }

            if (Schema::hasColumn('contact_groups', 'is_active')) {
                $table->boolean('is_active')->default(true)->change();
            } else {
                $table->boolean('is_active')->default(true)->after('beem_address_book_id');
            }

            if (!Schema::hasColumn('contact_groups', 'created_at')) {
                $table->timestamps();
            }
        });

        // Ensure the composite index exists
        $connection = Schema::getConnection();
        if (method_exists($connection, 'getDoctrineSchemaManager')) {
            $schemaManager = $connection->getDoctrineSchemaManager();
            $indexes = $schemaManager->listTableIndexes('contact_groups');
            if (!array_key_exists('contact_groups_user_id_is_default_index', $indexes)) {
                Schema::table('contact_groups', function (Blueprint $table) {
                    $table->index(['user_id', 'is_default'], 'contact_groups_user_id_is_default_index');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contact_groups', function (Blueprint $table) {
            if (Schema::hasColumn('contact_groups', 'is_default')) {
                $table->dropColumn('is_default');
            }

            if (Schema::hasColumn('contact_groups', 'color')) {
                $table->dropColumn('color');
            }

            if (Schema::hasColumn('contact_groups', 'beem_address_book_id')) {
                $table->dropColumn('beem_address_book_id');
            }
        });

        $connection = Schema::getConnection();
        if (method_exists($connection, 'getDoctrineSchemaManager')) {
            $schemaManager = $connection->getDoctrineSchemaManager();
            $indexes = $schemaManager->listTableIndexes('contact_groups');
            if (array_key_exists('contact_groups_user_id_is_default_index', $indexes)) {
                Schema::table('contact_groups', function (Blueprint $table) {
                    $table->dropIndex('contact_groups_user_id_is_default_index');
                });
            }
        }
    }
};
