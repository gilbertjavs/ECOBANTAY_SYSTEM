<?php
require __DIR__ . '/../includes/init.php';
require_role('Admin');

$rows = q('SELECT * FROM reports ORDER BY date_reported DESC, id DESC')->fetchAll();

/** Stops spreadsheet formula injection when the CSV is opened in Excel. */
function csv_safe($v): string
{
    $v = (string)$v;
    return ($v !== '' && strpos("=+-@\t\r", $v[0]) !== false) ? "'" . $v : $v;
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="ecobantay-reports.csv"');
$out = fopen('php://output', 'w');
fputcsv($out, ['id', 'reporter', 'location', 'wasteType', 'problemType', 'status', 'dateReported', 'description']);
foreach ($rows as $r) {
    fputcsv($out, [rid($r['id']), csv_safe($r['reporter']), csv_safe($r['location']), $r['waste_type'], $r['problem_type'],
                   $r['status'], $r['date_reported'], csv_safe($r['description'])]);
}
fclose($out);
