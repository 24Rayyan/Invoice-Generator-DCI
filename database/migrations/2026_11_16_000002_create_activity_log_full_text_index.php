<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("CREATE VIRTUAL TABLE activity_logs_fts USING fts5(description, user_name, module_name, content='activity_logs', content_rowid='id')");
        DB::unprepared("CREATE TRIGGER activity_logs_fts_insert AFTER INSERT ON activity_logs BEGIN
            INSERT INTO activity_logs_fts(rowid, description, user_name, module_name)
            VALUES (new.id, new.description, new.user_name, new.module_name);
        END");
        DB::unprepared("CREATE TRIGGER activity_logs_fts_delete AFTER DELETE ON activity_logs BEGIN
            INSERT INTO activity_logs_fts(activity_logs_fts, rowid, description, user_name, module_name)
            VALUES ('delete', old.id, old.description, old.user_name, old.module_name);
        END");
        DB::unprepared("CREATE TRIGGER activity_logs_fts_update AFTER UPDATE ON activity_logs BEGIN
            INSERT INTO activity_logs_fts(activity_logs_fts, rowid, description, user_name, module_name)
            VALUES ('delete', old.id, old.description, old.user_name, old.module_name);
            INSERT INTO activity_logs_fts(rowid, description, user_name, module_name)
            VALUES (new.id, new.description, new.user_name, new.module_name);
        END");
        DB::statement("INSERT INTO activity_logs_fts(activity_logs_fts) VALUES ('rebuild')");
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS activity_logs_fts_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS activity_logs_fts_delete');
        DB::unprepared('DROP TRIGGER IF EXISTS activity_logs_fts_update');
        DB::statement('DROP TABLE IF EXISTS activity_logs_fts');
    }
};
