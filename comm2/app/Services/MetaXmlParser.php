<?php

declare(strict_types=1);

namespace App\Services;

use DOMDocument;
use DOMXPath;
use InvalidArgumentException;
use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class MetaXmlParser
{
    private const MAX_FILES = 1000;

    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB per file

    /**
     * Parse meta.xml from ZIP archive
     *
     * @param  string  $zipPath  Path to the ZIP file
     * @return array Parsed meta.xml data
     *
     * @throws InvalidArgumentException|RuntimeException
     */
    public function parse(string $zipPath): array
    {
        $zip = new ZipArchive;
        $result = $zip->open($zipPath);

        if ($result !== true) {
            throw new InvalidArgumentException("Failed to open ZIP file: {$zipPath}");
        }

        try {
            // Validate ZIP structure and security
            $this->validateZipSecurity($zip);

            // Read meta.xml from ZIP
            $metaXmlContent = $zip->getFromName('meta.xml');

            if ($metaXmlContent === false) {
                throw new InvalidArgumentException('meta.xml not found in ZIP archive');
            }

            // Parse XML using DOMDocument for reliable attribute access
            $dom = new DOMDocument;
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = false;

            $loaded = @$dom->loadXML($metaXmlContent);

            if (! $loaded) {
                throw new InvalidArgumentException('Invalid XML format in meta.xml');
            }

            // Also parse with SimpleXML for backward compatibility with other parts
            $xml = @simplexml_load_string($metaXmlContent);
            if ($xml === false) {
                throw new InvalidArgumentException('Invalid XML format in meta.xml');
            }

            // Debug: Log XML content and structure
            \Log::debug('Parsing meta.xml', [
                'xml_content_preview' => substr($metaXmlContent, 0, 500),
            ]);

            // Validate and extract data using DOMDocument for attributes
            return $this->extractMetaData($dom, $xml);
        } finally {
            $zip->close();
        }
    }

    /**
     * Validate ZIP security (file count, file sizes, zip slip protection)
     */
    private function validateZipSecurity(ZipArchive $zip): void
    {
        $fileCount = $zip->numFiles;

        if ($fileCount > self::MAX_FILES) {
            throw new InvalidArgumentException('ZIP contains too many files (max: '.self::MAX_FILES.')');
        }

        for ($i = 0; $i < $fileCount; $i++) {
            $entryName = $zip->getNameIndex($i);

            if ($entryName === false) {
                continue;
            }

            // Zip slip protection: reject paths with ..
            if (str_contains($entryName, '..')) {
                throw new InvalidArgumentException("Invalid path in ZIP: {$entryName} (contains '..')");
            }

            // Reject absolute paths
            if ($this->isAbsolutePath($entryName)) {
                throw new InvalidArgumentException("Invalid path in ZIP: {$entryName} (absolute path)");
            }

            // Check file size
            $fileInfo = $zip->statIndex($i);
            if ($fileInfo !== false && $fileInfo['size'] > self::MAX_FILE_SIZE) {
                throw new InvalidArgumentException("File too large in ZIP: {$entryName} (max: ".(self::MAX_FILE_SIZE / 1024 / 1024).'MB)');
            }
        }
    }

    /**
     * Check if path is absolute
     */
    private function isAbsolutePath(string $path): bool
    {
        // Check for Unix absolute paths
        if (str_starts_with($path, '/')) {
            return true;
        }

        // Check for Windows absolute paths (C:\, D:\, etc.)
        if (preg_match('/^[A-Za-z]:\\\\/', $path)) {
            return true;
        }

        // Check for Windows UNC paths (\\server\share)
        if (str_starts_with($path, '\\\\')) {
            return true;
        }

        return false;
    }

    /**
     * Extract and validate meta.xml data
     *
     * @param  DOMDocument  $dom  DOMDocument for reliable attribute access
     * @param  SimpleXMLElement  $xml  SimpleXML for other node access
     */
    private function extractMetaData(DOMDocument $dom, SimpleXMLElement $xml): array
    {
        // Validate root node
        if ($xml->getName() !== 'meta') {
            throw new InvalidArgumentException("Root node must be 'meta', found: ".$xml->getName());
        }

        // Validate info node
        if (! isset($xml->info)) {
            throw new InvalidArgumentException("Missing required 'info' node in meta.xml");
        }

        // Use DOMDocument to extract attributes reliably
        $xpath = new DOMXPath($dom);
        $infoNodes = $xpath->query('/meta/info');

        if ($infoNodes->length === 0) {
            throw new InvalidArgumentException("Missing required 'info' node in meta.xml");
        }

        $infoNode = $infoNodes->item(0);

        // Extract all attributes by iterating (most reliable method)
        $attrs = [];
        if ($infoNode->hasAttributes()) {
            foreach ($infoNode->attributes as $attr) {
                $attrs[$attr->nodeName] = trim($attr->nodeValue);
            }
        }

        // Extract required attributes from the map
        $author = $attrs['author'] ?? '';
        $longName = $attrs['name'] ?? '';
        $description = $attrs['description'] ?? '';
        $type = $attrs['type'] ?? '';
        $version = $attrs['version'] ?? '';

        \Log::debug('Attribute extraction via iteration', [
            'all_attributes' => $attrs,
            'extracted_version' => $version,
            'extracted_type' => $type,
        ]);

        // Validate required fields
        // Note: description is optional - it will be used as short_description if provided

        if (empty($type)) {
            throw new InvalidArgumentException("Missing required 'type' attribute in info node");
        }

        if ($version === '') {
            $version = '1.0.0';
        } elseif (! preg_match('/^\d+\.\d+\.\d+$/', $version)) {
            // Validate version format (semantic versioning) only when provided
            throw new InvalidArgumentException(
                "Invalid version format '{$version}'. Must be semantic version (e.g., 1.0.0)"
            );
        }

        // Validate type
        $validTypes = ['gamemode', 'script', 'map', 'misc'];
        if (! in_array($type, $validTypes, true)) {
            throw new InvalidArgumentException("Invalid type '{$type}'. Must be one of: ".implode(', ', $validTypes));
        }

        // Extract optional gamemodes
        $gamemodes = [];
        $gamemodesAttr = $infoNode->getAttribute('gamemodes');
        if (! empty($gamemodesAttr)) {
            $gamemodes = array_map('trim', explode(',', $gamemodesAttr));
            $gamemodes = array_filter($gamemodes);
        }

        // Validate at least one content node exists
        $hasContent = isset($xml->script) || isset($xml->map) || isset($xml->html) || isset($xml->config) || isset($xml->settings);

        if (! $hasContent) {
            throw new InvalidArgumentException('meta.xml must contain at least one of: script, map, html, config, settings');
        }

        // Extract min_mta_version
        $minMtaVersion = null;
        if (isset($xml->min_mta_version)) {
            $minMtaVersion = [
                'client' => (string) ($xml->min_mta_version['client'] ?? ''),
                'server' => (string) ($xml->min_mta_version['server'] ?? ''),
                'both' => (string) ($xml->min_mta_version['both'] ?? ''),
            ];

            // Validate MTA version format if provided
            // Minimum format: MAJOR.MINOR.MAINTENANCE (e.g., 1.3.4)
            // Full format: MAJOR.MINOR.MAINTENANCE-BUILDTYPE.BUILDNUMBER (e.g., 1.6.0-9.22279.0 or 1.1.1-9.03250)
            foreach ($minMtaVersion as $key => $mtaVersion) {
                if (! empty($mtaVersion)) {
                    // Accept formats:
                    // - MAJOR.MINOR.MAINTENANCE (minimum required, e.g., 1.3.4)
                    // - MAJOR.MINOR.MAINTENANCE-BUILDTYPE.BUILDNUMBER (full format, e.g., 1.6.0-9.22279.0)
                    // - MAJOR.MINOR.MAINTENANCE-BUILDTYPE.BUILDNUMBER (without trailing .0, e.g., 1.1.1-9.03250)
                    $pattern = '/^\d+\.\d+\.\d+(-\d+\.\d+(\.\d+)?)?$/';
                    if (! preg_match($pattern, $mtaVersion)) {
                        throw new InvalidArgumentException(
                            "Invalid MTA version format in min_mta_version.{$key}: {$mtaVersion}. ".
                            'Expected format: MAJOR.MINOR.MAINTENANCE (e.g., 1.3.4) or MAJOR.MINOR.MAINTENANCE-BUILDTYPE.BUILDNUMBER (e.g., 1.6.0-9.22279.0 or 1.1.1-9.03250)'
                        );
                    }
                }
            }
        }

        // Extract oop status
        $oopEnabled = false;
        if (isset($xml->oop)) {
            $oopValue = strtolower(trim((string) $xml->oop));
            $oopEnabled = in_array($oopValue, ['true', '1', 'yes'], true);
        }

        return [
            'author' => $author,
            'name' => $longName, // Long name (optional, can be empty)
            'description' => $description,
            'type' => $type,
            'version' => $version,
            'gamemodes' => $gamemodes,
            'min_mta_version' => $minMtaVersion,
            'oop_enabled' => $oopEnabled,
        ];
    }
}
