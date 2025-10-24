<?php

return [
    // Threshold hari untuk status "Akan Jatuh Tempo" (STNK/KIR)
    'soon_due_days' => (int) env('DASHBOARD_SOON_DUE_DAYS', 30),

    // Ambang batas admin fee tinggi (Rp)
    'outlier_admin_fee' => (int) env('DASHBOARD_OUTLIER_ADMIN_FEE', 100000),

    // Limit jumlah baris per tabel dashboard
    'table_limit' => (int) env('DASHBOARD_TABLE_LIMIT', 10),
];