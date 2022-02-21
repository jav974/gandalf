<?php

namespace App\Handler;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class YtDlpHandler
{
    private string $ytDlpPath;
    private string $ffmpegPath;
    private string $outputPath;

    public function __construct(string $ytDlpPath, string $ffmpegPath, string $outputPath)
    {
        $this->ytDlpPath = $ytDlpPath;
        $this->ffmpegPath = $ffmpegPath;
        $this->outputPath = $outputPath;
    }

    public function extractVideo(string $url): string
    {
        $filename = $this->getFilename($url);
        $process = new Process([$this->ytDlpPath, $url, '-o', "$this->outputPath/$filename.%(ext)s"]);
        $process->setTimeout(3600);
        $outputFilename = '';

        $process->run(function(string $type, string $line) use(&$outputFilename) {
            if ($type !== 'out') {
                return ;
            }

            if (preg_match('/\[download] (?<dest>.+) has already been downloaded/', $line, $match)) {
                $outputFilename = $match['dest'];
            }

            if (preg_match('/\[download] Destination: (?<dest>.+)/', $line, $match)) {
                $outputFilename = $match['dest'];
            }
        });

        // executes after the command finishes
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $outputFilename;
    }

    public function extractAudio(string $url): string
    {
        $filename = $this->getFilename($url);
        $process = new Process([$this->ytDlpPath, "-x", $url, "-o", "$this->outputPath/$filename.%(ext)s", '--ffmpeg-location', $this->ffmpegPath, '--audio-format', 'mp3']);
        $process->setTimeout(3600);
        $outputFilename = '';

        $process->run(function(string $type, string $line) use(&$outputFilename) {
            if ($type !== 'out') {
                return ;
            }

            if (preg_match('/\[ExtractAudio] Destination: (?<dest>.+)/', $line, $match)) {
                $outputFilename = $match['dest'];
            }
        });

        // executes after the command finishes
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $outputFilename;
    }

    public function getMetadata(string $url)
    {
        $process = new Process([$this->ytDlpPath,  "-J", "$url"]);
        $process->run();

        // executes after the command finishes
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return json_decode($process->getOutput(), true);
    }

    public function getFilename(string $url): string
    {
        $metadata = $this->getMetadata($url);

        return $metadata['title'];
    }
}
