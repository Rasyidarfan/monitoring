<?php

namespace App\Services;

class CsvMappingService
{
    /**
     * Mapping patterns for different CSV formats
     * Each field can have multiple possible header names (case-insensitive)
     */
    private array $mappings = [
        'target' => [
            'Target',
            'TARGET',
        ],
        'open' => [
            'Open',
            'OPEN',
        ],
        'submitted' => [
            'Submitted by PPL',
            'SUBMITTED BY PPL',
            'Submitted',
            'SUBMITTED',
        ],
        'approved' => [
            'Approved by PML',
            'APPROVED BY PML',
            'Completed by PML',
            'COMPLETED BY PML',
            'Approved',
            'APPROVED',
            'Completed',
            'COMPLETED',
        ],
        'rejected' => [
            'Rejected by PML',
            'REJECTED BY PML',
            'Rejected',
            'REJECTED',
        ],
    ];

    /**
     * Map CSV headers to our database fields
     *
     * @param array $headers Raw CSV headers
     * @return array Mapping of our field names to CSV column indices
     */
    public function mapHeaders(array $headers): array
    {
        $headerMap = [];

        foreach ($this->mappings as $ourField => $possibleHeaders) {
            $headerMap[$ourField] = $this->findHeaderIndex($headers, $possibleHeaders);
        }

        return $headerMap;
    }

    /**
     * Find the index of the first matching header
     *
     * @param array $headers CSV headers
     * @param array $possibleNames Possible header names to match
     * @return int|null Index of matched header, or null if not found
     */
    private function findHeaderIndex(array $headers, array $possibleNames): ?int
    {
        foreach ($headers as $index => $header) {
            $cleanHeader = trim($header, " \t\n\r\0\x0B'\"");

            foreach ($possibleNames as $possibleName) {
                if (strcasecmp($cleanHeader, $possibleName) === 0) {
                    return $index;
                }
            }
        }

        return null;
    }

    /**
     * Extract values from CSV row using the header map
     *
     * @param array $row CSV row data
     * @param array $headerMap Header mapping from mapHeaders()
     * @return array Extracted values with our field names
     */
    public function extractValues(array $row, array $headerMap): array
    {
        $values = [];

        foreach ($headerMap as $ourField => $csvIndex) {
            if ($csvIndex !== null && isset($row[$csvIndex])) {
                $values[$ourField] = $this->cleanValue($row[$csvIndex]);
            } else {
                $values[$ourField] = 0; // Default to 0 if field not found
            }
        }

        return $values;
    }

    /**
     * Clean and convert value to integer
     *
     * @param mixed $value Raw value from CSV
     * @return int Cleaned integer value
     */
    private function cleanValue($value): int
    {
        // Remove whitespace
        $value = trim($value);

        // Handle empty values
        if ($value === '' || $value === null) {
            return 0;
        }

        // Convert to integer (handles both "10" and "10.0")
        return (int) $value;
    }

    /**
     * Parse village code and name from format: [9702010001] WAMENA
     *
     * @param string $desaField Raw Desa field value
     * @return array|null ['code' => '9702010001', 'name' => 'WAMENA'] or null if invalid
     */
    public function parseVillageField(string $desaField): ?array
    {
        if (preg_match('/\[([^\]]+)\]\s*(.+)/', $desaField, $matches)) {
            return [
                'code' => trim($matches[1]),
                'name' => trim($matches[2]),
            ];
        }

        return null;
    }

    /**
     * Detect if CSV uses old format (all UPPERCASE headers)
     *
     * @param array $headers CSV headers
     * @return bool True if old format detected
     */
    public function isOldFormat(array $headers): bool
    {
        // Check if headers contain UPPERCASE versions
        $uppercaseCount = 0;
        foreach ($headers as $header) {
            if (strtoupper($header) === $header && strlen($header) > 3) {
                $uppercaseCount++;
            }
        }

        return $uppercaseCount >= 3; // If 3+ headers are uppercase, consider it old format
    }

    /**
     * Get format description for debugging
     *
     * @param array $headerMap Header mapping
     * @param array $headers Original headers
     * @return string Description of detected format
     */
    public function getFormatDescription(array $headerMap, array $headers): string
    {
        $description = "Detected CSV format:\n";

        foreach ($headerMap as $ourField => $csvIndex) {
            if ($csvIndex !== null) {
                $description .= "  - {$ourField}: '{$headers[$csvIndex]}' (column {$csvIndex})\n";
            } else {
                $description .= "  - {$ourField}: NOT FOUND\n";
            }
        }

        return $description;
    }
}
