<?php

declare(strict_types=1);

namespace PdfMerger;

use setasign\Fpdi\Fpdi;

/**
 * Merge multiple PDF files into a single document.
 */
class PdfMerger
{
    /**
     * @param string[] $files Absolute paths to the files that should be merged.
     *
     * @return string The binary string representation of the merged PDF.
     */
    public function merge(array $files): string
    {
        if ($files === []) {
            throw new \InvalidArgumentException('At least one PDF is required to merge.');
        }

        $merger = new Fpdi();

        foreach ($files as $file) {
            if (!is_readable($file)) {
                throw new \RuntimeException(sprintf('PDF "%s" is not readable.', $file));
            }

            $pageCount = $merger->setSourceFile($file);

            for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
                $template = $merger->importPage($pageNumber);
                $size = $merger->getTemplateSize($template);

                $merger->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $merger->useTemplate($template);
            }
        }

        return $merger->Output('S');
    }
}
