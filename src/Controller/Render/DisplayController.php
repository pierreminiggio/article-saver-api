<?php

namespace App\Controller\Render;

use App\Query\Render\CurrentRenderStatusForVideoQuery;

class DisplayController
{
    public function __construct(private CurrentRenderStatusForVideoQuery $query)
    {
    }

    public function __invoke(int $videoId): void
    {
        $renderStatus = $this->query->execute($videoId);

        if ($renderStatus === null || ! $renderStatus->hasRenderedFile()) {
            http_response_code(404);

            return;
        }

        $filePath = $renderStatus->filePath;
        $explodedFilePath = explode(DIRECTORY_SEPARATOR, $filePath);
        $filename = $explodedFilePath[count($explodedFilePath) - 1];

        header('Content-type: video/mp4');
        header('Content-length: ' . filesize($filePath));
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('X-Pad: avoid browser bug');
        header('Cache-Control: no-cache');
        readfile($filePath);
    }
}
