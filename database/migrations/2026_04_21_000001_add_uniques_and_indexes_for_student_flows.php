<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const USERS_TESTS_RESULTS_UNIQUE = 'utr_user_module_unique';
    private const USERS_CATEGORY_UNIQUE = 'uc_user_category_unique';
    private const RESULT_CATEGORIES_UNIQUE = 'rc_module_value_unique';

    private const MESSAGES_CONVERSATION_ROLE_READ_ID = 'msg_conv_role_read_id_idx';
    private const MESSAGES_ROLE_READ_CONVERSATION = 'msg_role_read_conv_idx';
    private const CONVERSATIONS_STUDENT_LAST_MESSAGE = 'conv_student_last_msg_id_idx';
    private const CONVERSATIONS_CHANNEL_LAST_MESSAGE = 'conv_channel_last_msg_id_idx';

    private const USERS_LOGIN_INDEX = 'users_login_idx';
    private const USERS_ROLE_CREATED_INDEX = 'users_role_created_idx';
    private const USERS_ROLE_GROUP_INDEX = 'users_role_group_idx';
    private const USERS_ROLE_SPECIALITY_INDEX = 'users_role_speciality_idx';
    private const USERS_ROLE_FACULITY_INDEX = 'users_role_faculity_idx';
    private const USERS_ROLE_LEVEL_INDEX = 'users_role_level_idx';

    public function up(): void
    {
        $this->deduplicateUsersTestsResults();
        $this->deduplicateUsersCategory();
        $this->deduplicateResultCategories();

        $this->addUniqueConstraints();
        $this->addConversationAndMessageIndexes();
        $this->addUserIndexes();
    }

    public function down(): void
    {
        $this->dropIndexIfExists('users_tests_results', self::USERS_TESTS_RESULTS_UNIQUE, 'unique');
        $this->dropIndexIfExists('users_category', self::USERS_CATEGORY_UNIQUE, 'unique');
        $this->dropIndexIfExists('result_categories', self::RESULT_CATEGORIES_UNIQUE, 'unique');

        $this->dropIndexIfExists('messages', self::MESSAGES_CONVERSATION_ROLE_READ_ID);
        $this->dropIndexIfExists('messages', self::MESSAGES_ROLE_READ_CONVERSATION);
        $this->dropIndexIfExists('conversations', self::CONVERSATIONS_STUDENT_LAST_MESSAGE);
        $this->dropIndexIfExists('conversations', self::CONVERSATIONS_CHANNEL_LAST_MESSAGE);

        $this->dropIndexIfExists('users', self::USERS_LOGIN_INDEX);
        $this->dropIndexIfExists('users', self::USERS_ROLE_CREATED_INDEX);
        $this->dropIndexIfExists('users', self::USERS_ROLE_GROUP_INDEX);
        $this->dropIndexIfExists('users', self::USERS_ROLE_SPECIALITY_INDEX);
        $this->dropIndexIfExists('users', self::USERS_ROLE_FACULITY_INDEX);
        $this->dropIndexIfExists('users', self::USERS_ROLE_LEVEL_INDEX);
    }

    private function deduplicateUsersTestsResults(): void
    {
        DB::table('users_tests_results')
            ->select('user_id', 'module_id')
            ->selectRaw('COUNT(*) as duplicate_count')
            ->groupBy('user_id', 'module_id')
            ->having('duplicate_count', '>', 1)
            ->orderBy('user_id')
            ->orderBy('module_id')
            ->get()
            ->each(function (object $duplicate): void {
                $rows = DB::table('users_tests_results')
                    ->where('user_id', $duplicate->user_id)
                    ->where('module_id', $duplicate->module_id)
                    ->orderBy('id')
                    ->get([
                        'id',
                        'result_fake',
                        'result_real',
                        'diagnosis',
                        'created_at',
                        'updated_at',
                    ]);

                $keep = $rows->first();

                if (! $keep) {
                    return;
                }

                DB::table('users_tests_results')
                    ->where('id', $keep->id)
                    ->update([
                        'result_fake' => $this->preferLatestFilled($rows, 'result_fake'),
                        'result_real' => $this->preferLatestFilled($rows, 'result_real'),
                        'diagnosis' => $this->preferLatestFilled($rows, 'diagnosis'),
                        'created_at' => $this->preferEarliestTimestamp($rows, 'created_at'),
                        'updated_at' => $this->preferLatestTimestamp($rows, 'updated_at'),
                    ]);

                DB::table('users_tests_results')
                    ->where('user_id', $duplicate->user_id)
                    ->where('module_id', $duplicate->module_id)
                    ->where('id', '!=', $keep->id)
                    ->delete();
            });
    }

    private function deduplicateUsersCategory(): void
    {
        DB::table('users_category')
            ->select('user_id', 'category_id')
            ->selectRaw('COUNT(*) as duplicate_count')
            ->groupBy('user_id', 'category_id')
            ->having('duplicate_count', '>', 1)
            ->orderBy('user_id')
            ->orderBy('category_id')
            ->get()
            ->each(function (object $duplicate): void {
                $rows = DB::table('users_category')
                    ->where('user_id', $duplicate->user_id)
                    ->where('category_id', $duplicate->category_id)
                    ->orderBy('id')
                    ->get(['id', 'created_at', 'updated_at']);

                $keep = $rows->first();

                if (! $keep) {
                    return;
                }

                DB::table('users_category')
                    ->where('id', $keep->id)
                    ->update([
                        'created_at' => $this->preferEarliestTimestamp($rows, 'created_at'),
                        'updated_at' => $this->preferLatestTimestamp($rows, 'updated_at'),
                    ]);

                DB::table('users_category')
                    ->where('user_id', $duplicate->user_id)
                    ->where('category_id', $duplicate->category_id)
                    ->where('id', '!=', $keep->id)
                    ->delete();
            });
    }

    private function deduplicateResultCategories(): void
    {
        DB::table('result_categories')
            ->whereNotNull('value')
            ->select('module_id', 'value')
            ->selectRaw('COUNT(*) as duplicate_count')
            ->groupBy('module_id', 'value')
            ->having('duplicate_count', '>', 1)
            ->orderBy('module_id')
            ->orderBy('value')
            ->get()
            ->each(function (object $duplicate): void {
                $rows = DB::table('result_categories')
                    ->where('module_id', $duplicate->module_id)
                    ->where('value', $duplicate->value)
                    ->orderBy('id')
                    ->get([
                        'id',
                        'name',
                        'diagnostic',
                        'fake_diagnostic',
                        'created_at',
                        'updated_at',
                    ]);

                $keep = $rows->first();

                if (! $keep) {
                    return;
                }

                DB::table('result_categories')
                    ->where('id', $keep->id)
                    ->update([
                        'name' => $this->preferLatestFilled($rows, 'name') ?? $keep->name,
                        'diagnostic' => $this->preferLatestFilled($rows, 'diagnostic'),
                        'fake_diagnostic' => $this->preferLatestFilled($rows, 'fake_diagnostic'),
                        'created_at' => $this->preferEarliestTimestamp($rows, 'created_at'),
                        'updated_at' => $this->preferLatestTimestamp($rows, 'updated_at'),
                    ]);

                DB::table('result_categories')
                    ->where('module_id', $duplicate->module_id)
                    ->where('value', $duplicate->value)
                    ->where('id', '!=', $keep->id)
                    ->delete();
            });
    }

    private function addUniqueConstraints(): void
    {
        if (! Schema::hasIndex('users_tests_results', ['user_id', 'module_id'], 'unique')) {
            Schema::table('users_tests_results', function (Blueprint $table): void {
                $table->unique(['user_id', 'module_id'], self::USERS_TESTS_RESULTS_UNIQUE);
            });
        }

        if (! Schema::hasIndex('users_category', ['user_id', 'category_id'], 'unique')) {
            Schema::table('users_category', function (Blueprint $table): void {
                $table->unique(['user_id', 'category_id'], self::USERS_CATEGORY_UNIQUE);
            });
        }

        if (! Schema::hasIndex('result_categories', ['module_id', 'value'], 'unique')) {
            Schema::table('result_categories', function (Blueprint $table): void {
                $table->unique(['module_id', 'value'], self::RESULT_CATEGORIES_UNIQUE);
            });
        }
    }

    private function addConversationAndMessageIndexes(): void
    {
        if (! Schema::hasIndex('messages', ['conversation_id', 'sender_role', 'read_at', 'id'])) {
            Schema::table('messages', function (Blueprint $table): void {
                $table->index(
                    ['conversation_id', 'sender_role', 'read_at', 'id'],
                    self::MESSAGES_CONVERSATION_ROLE_READ_ID
                );
            });
        }

        if (! Schema::hasIndex('messages', ['sender_role', 'read_at', 'conversation_id'])) {
            Schema::table('messages', function (Blueprint $table): void {
                $table->index(
                    ['sender_role', 'read_at', 'conversation_id'],
                    self::MESSAGES_ROLE_READ_CONVERSATION
                );
            });
        }

        if (! Schema::hasIndex('conversations', ['student_id', 'last_message_at', 'id'])) {
            Schema::table('conversations', function (Blueprint $table): void {
                $table->index(
                    ['student_id', 'last_message_at', 'id'],
                    self::CONVERSATIONS_STUDENT_LAST_MESSAGE
                );
            });
        }

        if (! Schema::hasIndex('conversations', ['channel', 'last_message_at', 'id'])) {
            Schema::table('conversations', function (Blueprint $table): void {
                $table->index(
                    ['channel', 'last_message_at', 'id'],
                    self::CONVERSATIONS_CHANNEL_LAST_MESSAGE
                );
            });
        }
    }

    private function addUserIndexes(): void
    {
        if (Schema::hasColumn('users', 'login') && ! Schema::hasIndex('users', ['login'])) {
            Schema::table('users', function (Blueprint $table): void {
                $table->index('login', self::USERS_LOGIN_INDEX);
            });
        }

        if ($this->usersHasColumns(['role', 'created_at']) && ! Schema::hasIndex('users', ['role', 'created_at'])) {
            Schema::table('users', function (Blueprint $table): void {
                $table->index(['role', 'created_at'], self::USERS_ROLE_CREATED_INDEX);
            });
        }

        if ($this->usersHasColumns(['role', 'group_id']) && ! Schema::hasIndex('users', ['role', 'group_id'])) {
            Schema::table('users', function (Blueprint $table): void {
                $table->index(['role', 'group_id'], self::USERS_ROLE_GROUP_INDEX);
            });
        }

        if ($this->usersHasColumns(['role', 'speciality_id']) && ! Schema::hasIndex('users', ['role', 'speciality_id'])) {
            Schema::table('users', function (Blueprint $table): void {
                $table->index(['role', 'speciality_id'], self::USERS_ROLE_SPECIALITY_INDEX);
            });
        }

        if ($this->usersHasColumns(['role', 'faculity_id']) && ! Schema::hasIndex('users', ['role', 'faculity_id'])) {
            Schema::table('users', function (Blueprint $table): void {
                $table->index(['role', 'faculity_id'], self::USERS_ROLE_FACULITY_INDEX);
            });
        }

        if ($this->usersHasColumns(['role', 'level']) && ! Schema::hasIndex('users', ['role', 'level'])) {
            Schema::table('users', function (Blueprint $table): void {
                $table->index(['role', 'level'], self::USERS_ROLE_LEVEL_INDEX);
            });
        }
    }

    private function usersHasColumns(array $columns): bool
    {
        foreach ($columns as $column) {
            if (! Schema::hasColumn('users', $column)) {
                return false;
            }
        }

        return true;
    }

    private function dropIndexIfExists(string $table, string $indexName, ?string $type = null): void
    {
        if (! Schema::hasIndex($table, $indexName, $type)) {
            return;
        }

        Schema::table($table, function (Blueprint $tableBlueprint) use ($indexName, $type): void {
            if ($type === 'unique') {
                $tableBlueprint->dropUnique($indexName);

                return;
            }

            $tableBlueprint->dropIndex($indexName);
        });
    }

    private function preferLatestFilled(Collection $rows, string $column): mixed
    {
        $row = $rows
            ->reverse()
            ->first(fn (object $row): bool => filled($row->{$column}));

        return $row?->{$column};
    }

    private function preferEarliestTimestamp(Collection $rows, string $column): mixed
    {
        return $rows
            ->pluck($column)
            ->filter()
            ->sort()
            ->first();
    }

    private function preferLatestTimestamp(Collection $rows, string $column): mixed
    {
        return $rows
            ->pluck($column)
            ->filter()
            ->sort()
            ->last();
    }
};
