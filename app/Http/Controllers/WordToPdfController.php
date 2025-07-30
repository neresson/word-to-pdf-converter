<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Str;

class WordToPdfController extends Controller
{
    public function convert(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:doc,docx|max:10240', // 10MB max
            'text_replacements' => 'nullable|string',
        ]);

        try {
            $uploadedFile = $request->file('file');
            $tempPath = $uploadedFile->store('temp');
            $fullPath = Storage::path($tempPath);

            $textReplacementsJson = $request->input('text_replacements', '[]');
            $textReplacements = json_decode($textReplacementsJson, true) ?? [];
            error_log("Text replacements received: " . print_r($textReplacements, true));

            if (!empty($textReplacements)) {
                $fullPath = $this->performDirectTextReplacement($fullPath, $textReplacements);
            }

            return $this->convertWithLibreOffice($fullPath, $uploadedFile->getClientOriginalName(), $tempPath);

        } catch (\Exception $e) {
            if (isset($tempPath)) {
                Storage::delete($tempPath);
            }
            if (isset($processedPath) && file_exists($processedPath)) {
                unlink($processedPath);
            }

            return response()->json([
                'error' => 'Failed to convert document: ' . $e->getMessage()
            ], 500);
        }
    }

    private function convertWithLibreOffice($filePath, $originalName, $tempPath)
    {
        $libreOfficeCmd = $this->findLibreOfficeCommand();

        if ($libreOfficeCmd) {
            try {
                $outputDir = storage_path('app/temp');
                $command = "{$libreOfficeCmd} --headless --convert-to pdf --outdir {$outputDir} " . escapeshellarg($filePath);

                exec($command . ' 2>&1', $output, $returnCode);

                if ($returnCode === 0) {
                    $pdfPath = $outputDir . '/' . pathinfo($filePath, PATHINFO_FILENAME) . '.pdf';

                    if (file_exists($pdfPath)) {
                        $pdfContent = file_get_contents($pdfPath);

                        // Clean up
                        Storage::delete($tempPath);
                        if (file_exists($filePath) && $filePath !== Storage::path($tempPath)) {
                            unlink($filePath);
                        }
                        unlink($pdfPath);

                        $filename = pathinfo($originalName, PATHINFO_FILENAME) . '.pdf';

                        return response($pdfContent)
                            ->header('Content-Type', 'application/pdf')
                            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                            ->header('Pragma', 'no-cache')
                            ->header('Expires', '0');
                    }
                }
            } catch (\Exception $e) {
                error_log("LibreOffice conversion failed: " . $e->getMessage());
            }
        }

        return $this->convertWithHtml($filePath, $originalName, $tempPath);
    }

    private function findLibreOfficeCommand()
    {
        $commands = ['libreoffice', 'soffice', '/usr/bin/libreoffice', '/usr/bin/soffice'];

        foreach ($commands as $cmd) {
            exec("which {$cmd} 2>/dev/null", $output, $returnCode);
            if ($returnCode === 0) {
                return $cmd;
            }
        }

        return null;
    }

    private function convertWithHtml($filePath, $originalName, $tempPath)
    {
        $phpWord = IOFactory::load($filePath);

        $htmlContent = $this->generateCleanHtml($phpWord);

        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($htmlContent);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $pdfContent = $dompdf->output();

        Storage::delete($tempPath);
        if (file_exists($filePath) && $filePath !== Storage::path($tempPath)) {
            unlink($filePath);
        }

        $filename = pathinfo($originalName, PATHINFO_FILENAME) . '.pdf';

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    private function generateCleanHtml($phpWord)
    {
        $html = '<html><head><style>
            body { font-family: Arial, sans-serif; font-size: 12pt; line-height: 1.4; margin: 20px; }
            p { margin: 6pt 0; }
            table { width: 100%; border-collapse: collapse; margin: 10pt 0; }
            td, th { border: 1px solid #ccc; padding: 8pt; vertical-align: top; }
            h1 { font-size: 18pt; margin: 12pt 0 6pt 0; }
            h2 { font-size: 16pt; margin: 10pt 0 6pt 0; }
            h3 { font-size: 14pt; margin: 8pt 0 4pt 0; }
        </style></head><body>';

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                $html .= $this->convertElementToHtml($element);
            }
        }

        $html .= '</body></html>';

        return $html;
    }

    private function convertElementToHtml($element)
    {
        $class = get_class($element);
        $html = '';

        switch ($class) {
            case 'PhpOffice\PhpWord\Element\TextRun':
                $html .= '<p>';
                foreach ($element->getElements() as $textElement) {
                    if (get_class($textElement) === 'PhpOffice\PhpWord\Element\Text') {
                        $text = htmlspecialchars($textElement->getText());
                        $html .= $text;
                    }
                }
                $html .= '</p>';
                break;

            case 'PhpOffice\PhpWord\Element\Text':
                $html .= '<p>' . htmlspecialchars($element->getText()) . '</p>';
                break;

            case 'PhpOffice\PhpWord\Element\Table':
                $html .= '<table>';
                foreach ($element->getRows() as $row) {
                    $html .= '<tr>';
                    foreach ($row->getCells() as $cell) {
                        $html .= '<td>';
                        foreach ($cell->getElements() as $cellElement) {
                            $html .= $this->convertElementToHtml($cellElement);
                        }
                        $html .= '</td>';
                    }
                    $html .= '</tr>';
                }
                $html .= '</table>';
                break;

            case 'PhpOffice\PhpWord\Element\TextBreak':
                $html .= '<br>';
                break;

            default:
                if (method_exists($element, 'getElements')) {
                    foreach ($element->getElements() as $subElement) {
                        $html .= $this->convertElementToHtml($subElement);
                    }
                }
                break;
        }

        return $html;
    }

    private function performDirectTextReplacement($filePath, $textReplacements)
    {
        try {
            $processedPath = storage_path('app/temp/' . Str::uuid() . '.docx');
            copy($filePath, $processedPath);

            $zip = new \ZipArchive();
            if ($zip->open($processedPath) !== TRUE) {
                error_log("Failed to open DOCX file as ZIP archive");
                return $filePath;
            }

            $filesToProcess = [
                'word/document.xml',
                'word/header1.xml',
                'word/header2.xml',
                'word/header3.xml',
                'word/footer1.xml',
                'word/footer2.xml',
                'word/footer3.xml'
            ];

            $replacementsMade = 0;

            foreach ($filesToProcess as $fileName) {
                $xmlContent = $zip->getFromName($fileName);
                if ($xmlContent === FALSE) {
                    continue; 
                }

                $originalContent = $xmlContent;
                $modifiedContent = $xmlContent;

                foreach ($textReplacements as $replacement) {
                    if (!isset($replacement['search']) || !isset($replacement['replace'])) {
                        continue;
                    }

                    $searchText = $replacement['search'];
                    $replaceText = $replacement['replace'];

                    error_log("Processing file {$fileName}: '{$searchText}' -> '{$replaceText}'");

                    $beforeCount = substr_count($modifiedContent, $searchText);

                    $modifiedContent = str_replace($searchText, $replaceText, $modifiedContent);

                    $searchTextEscaped = htmlspecialchars($searchText, ENT_XML1, 'UTF-8');
                    $replaceTextEscaped = htmlspecialchars($replaceText, ENT_XML1, 'UTF-8');
                    $modifiedContent = str_replace($searchTextEscaped, $replaceTextEscaped, $modifiedContent);


                    $afterCount = substr_count($modifiedContent, $searchText);
                    if ($beforeCount > $afterCount) {
                        $replacementsMade += ($beforeCount - $afterCount);
                        error_log("Made " . ($beforeCount - $afterCount) . " replacements in {$fileName}");
                    }
                }

                if ($originalContent !== $modifiedContent) {
                    $zip->deleteName($fileName);
                    $zip->addFromString($fileName, $modifiedContent);
                    error_log("Updated {$fileName} with modifications");
                }
            }

            $zip->close();

            error_log("Total replacements made: {$replacementsMade}");
            return $processedPath;

        } catch (\Exception $e) {
            error_log("Direct text replacement failed: " . $e->getMessage());
            return $filePath;
        }
    }
}