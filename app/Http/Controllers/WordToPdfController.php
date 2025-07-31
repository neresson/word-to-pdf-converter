<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WordToPdfController extends Controller
{
	public function convert(Request $request)
	{
		$request->validate([
			'file' => 'required|file|mimes:doc,docx|max:10240',
			'text_replacements' => 'nullable|string',
		]);

		try {
			$uploadedFile = $request->file('file');
			$tempPath = $uploadedFile->store('temp');
			$fullPath = Storage::path($tempPath);

			$textReplacementsJson = $request->input('text_replacements', '[]');
			$textReplacements = json_decode($textReplacementsJson, true) ?? [];

			if (!empty($textReplacements)) {
				$fullPath = $this->replaceText($fullPath, $textReplacements);
			}

			return $this->convertToPdf($fullPath, $uploadedFile->getClientOriginalName(), $tempPath);
		} catch (\Exception $e) {
			if (isset($tempPath)) {
				Storage::delete($tempPath);
			}
			if (isset($processedPath) && file_exists($processedPath)) {
				unlink($processedPath);
			}

			return response()->json(
				[
					'error' => 'Failed to convert document: ' . $e->getMessage(),
				],
				500,
			);
		}
	}

	private function convertToPdf($filePath, $originalName, $tempPath)
	{
		$libreOfficeCommand = $this->getLibreOfficeCommand();

		if (!$libreOfficeCommand) {
			throw new \Exception('LibreOffice is not installed or not found in PATH');
		}

		$outputDir = storage_path('app/temp');
		$command = "{$libreOfficeCommand} --headless --convert-to pdf --outdir {$outputDir} " . escapeshellarg($filePath);

		exec($command . ' 2>&1', $output, $returnCode);

		if ($returnCode !== 0) {
			throw new \Exception('LibreOffice conversion failed with return code: ' . $returnCode);
		}

		$pdfPath = $outputDir . '/' . pathinfo($filePath, PATHINFO_FILENAME) . '.pdf';

		if (!file_exists($pdfPath)) {
			throw new \Exception('PDF file was not created');
		}

		$pdfContent = file_get_contents($pdfPath);

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

	private function getLibreOfficeCommand()
	{
		$commands = ['libreoffice', 'soffice', '/usr/bin/libreoffice', '/usr/bin/soffice'];

		foreach ($commands as $command) {
			exec("which {$command} 2>/dev/null", $output, $returnCode);
			if ($returnCode === 0) {
				return $command;
			}
		}

		return null;
	}

	private function replaceText($filePath, $textReplacements)
	{
		try {
			$processedPath = storage_path('app/temp/' . Str::uuid() . '.docx');
			copy($filePath, $processedPath);

			$zip = new \ZipArchive();
			if ($zip->open($processedPath) !== true) {
				return $filePath;
			}

			$filesToProcess = [
				'word/document.xml',
				'word/header1.xml',
				'word/header2.xml',
				'word/header3.xml',
				'word/footer1.xml',
				'word/footer2.xml',
				'word/footer3.xml',
			];

			foreach ($filesToProcess as $fileName) {
				$xmlContent = $zip->getFromName($fileName);
				if ($xmlContent === false) {
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

					$modifiedContent = str_replace($searchText, $replaceText, $modifiedContent);

					$searchTextEscaped = htmlspecialchars($searchText, ENT_XML1, 'UTF-8');
					$replaceTextEscaped = htmlspecialchars($replaceText, ENT_XML1, 'UTF-8');
					$modifiedContent = str_replace($searchTextEscaped, $replaceTextEscaped, $modifiedContent);
				}

				if ($originalContent !== $modifiedContent) {
					$zip->deleteName($fileName);
					$zip->addFromString($fileName, $modifiedContent);
				}
			}

			$zip->close();
			return $processedPath;
		} catch (\Exception $e) {
			return $filePath;
		}
	}
}
