<?php

declare(strict_types=1);

use PdfMerger\PdfMerger;

$autoloadError = null;
$composerAutoload = __DIR__ . '/../vendor/autoload.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'PdfMerger\\';

    if (str_starts_with($class, $prefix)) {
        $relative = substr($class, strlen($prefix));
        $path = __DIR__ . '/../src/' . str_replace('\\', '/', $relative) . '.php';

        if (is_file($path)) {
            require_once $path;
        }
    }
});

if (is_file($composerAutoload)) {
    require_once $composerAutoload;
} else {
    $autoloadError = 'Composer dependencies are not installed. Run "composer install" before merging files.';
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($autoloadError !== null) {
        $errors[] = $autoloadError;
    } else {
        $uploadedFiles = normaliseUploadedFiles($_FILES['pdfs'] ?? []);
        $temporaryFiles = [];

        try {
            $fileInfo = finfo_open(FILEINFO_MIME_TYPE);

            foreach ($uploadedFiles as $file) {
                if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                    $errors[] = sprintf('Gagal mengunggah "%s" (kode kesalahan: %s).', $file['name'] ?? 'berkas', (string) $file['error']);
                    continue;
                }

                $mimeType = $file['tmp_name'] && $fileInfo ? finfo_file($fileInfo, $file['tmp_name']) : null;
                if ($mimeType !== 'application/pdf') {
                    $errors[] = sprintf('"%s" bukan PDF yang valid.', $file['name'] ?? 'berkas');
                    continue;
                }

                $temporaryPath = tempnam(sys_get_temp_dir(), 'pdf-merger-');
                if ($temporaryPath === false) {
                    $errors[] = 'Tidak dapat membuat berkas sementara.';
                    continue;
                }

                if (!move_uploaded_file($file['tmp_name'], $temporaryPath)) {
                    @unlink($temporaryPath);
                    $errors[] = sprintf('Tidak dapat memproses "%s".', $file['name'] ?? 'berkas');
                    continue;
                }

                $temporaryFiles[] = $temporaryPath;
            }

            if (count($temporaryFiles) < 2) {
                $errors[] = 'Unggah minimal dua PDF untuk digabungkan.';
            }

            if ($errors === []) {
                $merger = new PdfMerger();
                $mergedPdf = $merger->merge($temporaryFiles);

                foreach ($temporaryFiles as $temporaryFile) {
                    @unlink($temporaryFile);
                }

                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="merged-' . date('Ymd-His') . '.pdf"');
                header('Content-Length: ' . strlen($mergedPdf));

                echo $mergedPdf;
                exit;
            }
        } catch (Throwable $exception) {
            $errors[] = 'Terjadi kesalahan saat menggabungkan PDF: ' . $exception->getMessage();
        } finally {
            foreach ($temporaryFiles as $temporaryFile) {
                if (is_file($temporaryFile)) {
                    @unlink($temporaryFile);
                }
            }

            if (isset($fileInfo) && $fileInfo) {
                finfo_close($fileInfo);
            }
        }
    }
}

/**
 * @param array<string,mixed>|array<int,array<string,mixed>> $files
 * @return array<int,array<string,mixed>>
 */
function normaliseUploadedFiles(array $files): array
{
    if (!isset($files['name'])) {
        return [];
    }

    if (!is_array($files['name'])) {
        return [$files];
    }

    $normalised = [];

    foreach ($files['name'] as $index => $name) {
        $normalised[] = [
            'name' => $name,
            'type' => $files['type'][$index] ?? null,
            'tmp_name' => $files['tmp_name'][$index] ?? null,
            'error' => $files['error'][$index] ?? null,
            'size' => $files['size'][$index] ?? null,
        ];
    }

    return $normalised;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Merger</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <header>
        <h1>Penggabung PDF</h1>
        <p>Unggah minimal dua berkas PDF kemudian tekan "Gabungkan" untuk mengunduh hasilnya.</p>
    </header>

    <?php if ($errors !== []): ?>
        <div class="alert">
            <h2>Terjadi Kesalahan</h2>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php elseif ($autoloadError !== null): ?>
        <div class="alert">
            <?= htmlspecialchars($autoloadError, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="card">
        <label for="pdfs" class="input-label">Pilih PDF</label>
        <input type="file" name="pdfs[]" id="pdfs" accept="application/pdf" multiple required>

        <p class="hint">File akan diproses langsung di server setelah Anda mengirimkan formulir.</p>

        <button type="submit">Gabungkan</button>
    </form>

    <footer>
        <p>Pastikan Anda telah menjalankan <code>composer install</code> untuk mengaktifkan fitur penggabungan PDF.</p>
    </footer>
</div>
</body>
</html>
