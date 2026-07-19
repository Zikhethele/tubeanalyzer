<?php
declare(strict_types=1);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', 'php://stderr');

require_once __DIR__ . '/config/Config.php';
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/config/AdminAuth.php';
require_once __DIR__ . '/mailer_common.php';

adminRequireAuth();

const MAX_CSV_BYTES = 5 * 1024 * 1024;
const MAX_SKIPPED_SHOWN = 20;

/**
 * Parses an uploaded prospects CSV and upserts valid rows into `prospects`.
 * Expects a header row; only an `email` column is required, `first_name`,
 * `personal_reason`, and `source` are optional. Rows without a usable email
 * are skipped and reported rather than aborting the whole import.
 */
function processProspectsCsv(array $file, PDO $db): array {
    $summary = ['error' => null, 'upserted' => 0, 'skipped' => 0, 'skippedDetails' => []];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $summary['error'] = 'Upload failed (error code ' . $file['error'] . ').';
        return $summary;
    }
    if ($file['size'] > MAX_CSV_BYTES) {
        $summary['error'] = 'File is too large (max 5MB).';
        return $summary;
    }
    if (strtolower((string) pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'csv') {
        $summary['error'] = 'Please upload a .csv file.';
        return $summary;
    }

    $handle = fopen($file['tmp_name'], 'r');
    if ($handle === false) {
        $summary['error'] = 'Could not read the uploaded file.';
        return $summary;
    }

    $header = fgetcsv($handle);
    if ($header === false) {
        $summary['error'] = 'CSV appears to be empty.';
        fclose($handle);
        return $summary;
    }

    $colIndex = [];
    foreach ($header as $i => $name) {
        $colIndex[strtolower(trim((string) $name))] = $i;
    }
    if (!isset($colIndex['email'])) {
        $summary['error'] = "CSV must have an \"email\" column.";
        fclose($handle);
        return $summary;
    }

    $stmt = $db->prepare("
        INSERT INTO prospects (email, first_name, personal_reason, source)
        VALUES (:email, :first_name, :personal_reason, :source)
        ON CONFLICT (email) DO UPDATE
        SET first_name      = EXCLUDED.first_name,
            personal_reason = EXCLUDED.personal_reason,
            source          = EXCLUDED.source
    ");

    $line = 1;
    while (($row = fgetcsv($handle)) !== false) {
        $line++;
        if (count($row) === 1 && trim((string) $row[0]) === '') {
            continue; // blank line
        }

        $email = strtolower(trim((string) ($row[$colIndex['email']] ?? '')));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $summary['skipped']++;
            if (count($summary['skippedDetails']) < MAX_SKIPPED_SHOWN) {
                $summary['skippedDetails'][] = "line {$line}: invalid or missing email";
            }
            continue;
        }

        $firstName      = isset($colIndex['first_name'])      ? trim((string) ($row[$colIndex['first_name']] ?? ''))      : '';
        $personalReason = isset($colIndex['personal_reason']) ? trim((string) ($row[$colIndex['personal_reason']] ?? '')) : '';
        $source         = isset($colIndex['source'])          ? trim((string) ($row[$colIndex['source']] ?? ''))          : '';

        $stmt->execute([
            ':email'           => $email,
            ':first_name'      => $firstName !== '' ? $firstName : null,
            ':personal_reason' => $personalReason !== '' ? $personalReason : null,
            ':source'          => $source !== '' ? $source : 'csv-upload',
        ]);
        $summary['upserted']++;
    }

    fclose($handle);
    return $summary;
}

$db = Database::getInstance()->getConnection();
ensureMailerTables($db);

$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $result = processProspectsCsv($_FILES['csv_file'], $db);
}

$recent = $db->query(
    "SELECT email, first_name, personal_reason, source, created_at
     FROM prospects
     ORDER BY created_at DESC
     LIMIT 20"
)->fetchAll(PDO::FETCH_ASSOC);

$total = (int) $db->query("SELECT COUNT(*) FROM prospects")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Prospects — Admin — TubeAnalyzer</title>
<meta name="robots" content="noindex, nofollow">
<style>
    :root {
        --black:  #111;
        --muted:  #6b7280;
        --border: #e5e7eb;
        --bg:     #fafafa;
        --white:  #fff;
        --error:  #b91c1c;
        --success:#15803d;
        --radius: 6px;
    }
    * { box-sizing: border-box; }
    body {
        margin: 0;
        background: var(--bg);
        color: var(--black);
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        min-height: 100vh;
    }

    .wrap { max-width: 720px; margin: 0 auto; padding: 48px 24px 96px; }
    .top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 32px;
        gap: 16px;
        flex-wrap: wrap;
    }
    .admin-links { display: flex; gap: 4px; }
    .admin-links a {
        font-size: 13px;
        color: var(--muted);
        text-decoration: none;
        padding: 8px 12px;
        border-radius: var(--radius);
    }
    .admin-links a.active { color: var(--black); background: var(--white); border: 1px solid var(--border); font-weight: 500; }
    .admin-links a:hover { color: var(--black); }
    .signout {
        font-size: 13px;
        color: var(--muted);
        text-decoration: none;
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 8px 12px;
    }
    .signout:hover { border-color: var(--black); color: var(--black); }
    .signout:focus-visible { outline: 2px solid var(--black); outline-offset: 2px; }

    h1 { font-size: 20px; margin: 0 0 4px; }
    h2 { font-size: 15px; margin: 40px 0 12px; }
    .sub { font-size: 13px; color: var(--muted); margin: 0 0 24px; }

    .card { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); padding: 24px; }

    .dropzone {
        border: 1px dashed var(--border);
        border-radius: var(--radius);
        padding: 32px 20px;
        text-align: center;
        margin-bottom: 16px;
    }
    .dropzone input[type="file"] { font-size: 14px; }
    .hint { font-size: 12px; color: var(--muted); margin-top: 8px; }
    code { background: var(--bg); border: 1px solid var(--border); border-radius: 4px; padding: 1px 5px; font-size: 12px; }

    button {
        font-size: 14px;
        font-weight: 500;
        padding: 10px 18px;
        border: 1px solid var(--black);
        border-radius: var(--radius);
        background: var(--black);
        color: var(--white);
        cursor: pointer;
    }
    button:hover { background: #2a2a2a; }
    button:focus-visible { outline: 2px solid var(--black); outline-offset: 2px; }

    .result { border-radius: var(--radius); padding: 16px 20px; margin-bottom: 24px; font-size: 14px; }
    .result.ok { background: #f0fdf4; border: 1px solid #bbf7d0; color: var(--success); }
    .result.err { background: #fef2f2; border: 1px solid #fecaca; color: var(--error); }
    .result .details { margin-top: 8px; font-size: 12px; color: var(--muted); }
    .result ul { margin: 6px 0 0; padding-left: 18px; }

    table { width: 100%; border-collapse: collapse; background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; }
    th, td {
        text-align: left;
        padding: 10px 14px;
        font-size: 13px;
        border-bottom: 1px solid var(--border);
        vertical-align: top;
    }
    th { font-weight: 500; color: var(--muted); background: var(--bg); }
    tr:last-child td { border-bottom: none; }
    td.reason { color: var(--muted); max-width: 260px; }
    td.email { font-weight: 500; }
    .badge {
        display: inline-block;
        font-size: 11px;
        padding: 2px 8px;
        border-radius: 999px;
        border: 1px solid #fecaca;
        color: var(--error);
    }
    .empty { color: var(--muted); font-size: 14px; padding: 32px 0; }
</style>
</head>
<body>
    <div class="wrap">
        <?php adminNav('prospects'); ?>

        <h1>Prospects</h1>
        <p class="sub"><?= $total ?> total in the campaign queue</p>

        <?php if ($result !== null): ?>
            <?php if ($result['error']): ?>
                <div class="result err"><?= e($result['error']) ?></div>
            <?php else: ?>
                <div class="result ok">
                    Added/updated <?= (int) $result['upserted'] ?> prospect(s).
                    <?php if ($result['skipped'] > 0): ?>
                        Skipped <?= (int) $result['skipped'] ?> row(s).
                        <div class="details">
                            <ul>
                                <?php foreach ($result['skippedDetails'] as $detail): ?>
                                    <li><?= e($detail) ?></li>
                                <?php endforeach; ?>
                                <?php if ($result['skipped'] > count($result['skippedDetails'])): ?>
                                    <li>and <?= $result['skipped'] - count($result['skippedDetails']) ?> more…</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="card">
            <form method="post" enctype="multipart/form-data">
                <div class="dropzone">
                    <input type="file" name="csv_file" accept=".csv" required>
                    <p class="hint">
                        Header row required. Columns: <code>email</code> (required),
                        <code>first_name</code>, <code>personal_reason</code>, <code>source</code> — all optional.
                        Rows are matched on email; re-uploading updates existing prospects.
                    </p>
                </div>
                <button type="submit">Upload CSV</button>
            </form>
        </div>

        <p class="hint" style="margin-top:16px;">
            The prospect mailer skips any row with an empty <code>personal_reason</code> rather than send a
            generic-feeling email — fill that column in before relying on the campaign to send.
        </p>

        <h2>Recently added</h2>
        <?php if (empty($recent)): ?>
            <p class="empty">No prospects yet.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Name</th>
                        <th>Reason</th>
                        <th>Source</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($recent as $row): ?>
                    <tr>
                        <td class="email"><?= e((string) $row['email']) ?></td>
                        <td><?= e((string) ($row['first_name'] ?? '—')) ?></td>
                        <td class="reason">
                            <?php if (empty($row['personal_reason'])): ?>
                                <span class="badge">missing — won't send</span>
                            <?php else: ?>
                                <?= e((string) $row['personal_reason']) ?>
                            <?php endif; ?>
                        </td>
                        <td><?= e((string) ($row['source'] ?? '—')) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
