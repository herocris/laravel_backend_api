<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            CREATE VIEW data_graph_ammunition_confiscation AS
            SELECT
                c.date AS full_date,
                dc.ammunition_id,
                d.description AS ammunition_description,
                SUM(dc.amount) AS total_amount
            FROM ammunition_confiscations AS dc
            INNER JOIN confiscations AS c ON c.id = dc.confiscation_id
            INNER JOIN ammunitions AS d ON d.id = dc.ammunition_id
            WHERE dc.deleted_at IS NULL
              AND c.deleted_at IS NULL
            GROUP BY c.date, dc.ammunition_id, d.description
            SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS data_graph_ammunition_confiscation');
    }
};
