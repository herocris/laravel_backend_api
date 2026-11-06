<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            CREATE VIEW data_graph_drug_confiscation AS
            SELECT
                c.date AS full_date,
                dc.drug_id,
                d.description AS drug_description,
                SUM(dc.amount) AS total_amount,
                SUM(dc.weight) AS total_weight
            FROM drug_confiscations AS dc
            INNER JOIN confiscations AS c ON c.id = dc.confiscation_id
            INNER JOIN drugs AS d ON d.id = dc.drug_id
            WHERE dc.deleted_at IS NULL
              AND c.deleted_at IS NULL
            GROUP BY c.date, dc.drug_id, d.description
            SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS data_graph_drug_confiscation');
    }
};
