<?php

namespace App\Services;

/**
 * CSV Mapping Service
 *
 * Provides flexible CSV header mapping for monitoring data with support for:
 * - Multiple header name variations (case-insensitive)
 * - 4 Standard Categories for assignment status:
 *   1. open - Belum disubmit (OPEN)
 *   2. submitted - Disubmit oleh PPL (SUBMITTED BY PPL)
 *   3. approved - Disetujui/Selesai (Approved/Completed by PML OR Admin Kabupaten OR Edited by Admin)
 *   4. rejected - Ditolak (REJECTED BY PML OR Admin Kabupaten)
 *
 * Features:
 * - Case-insensitive header matching
 * - Flexible header variations (Completed, Approved, Edited all map to 'approved')
 * - Multiple column support: sums all columns mapping to the same status field
 * - Handles both query_1.csv (village-level) and query_2.csv (summary)
 * - Automatic village code/name parsing from "[code] name" format
 */
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
            'Submitted by Pencacah',
            'SUBMITTED BY PENCACAH',
            'Submitted',
            'SUBMITTED',
        ],
        'approved' => [
            'Approved by PML',
            'APPROVED BY PML',
            'Approved by Pengawas',
            'APPROVED BY PENGAWAS',
            'Completed by PML',
            'COMPLETED BY PML',
            'Completed by Admin Kabupaten',
            'COMPLETED BY ADMIN KABUPATEN',
            'Approved by Admin Kabupaten',
            'APPROVED BY ADMIN KABUPATEN',
            'Edited by Admin',
            'EDITED BY ADMIN',
            'Completed by Admin',
            'COMPLETED BY ADMIN',
            'Approved',
            'APPROVED',
            'Completed',
            'COMPLETED',
            'Edited',
            'EDITED',
        ],
        'rejected' => [
            'Rejected by PML',
            'REJECTED BY PML',
            'Rejected by Pengawas',
            'REJECTED BY PENGAWAS',
            'Rejected by Admin Kabupaten',
            'REJECTED BY ADMIN KABUPATEN',
            'Rejected',
            'REJECTED',
        ],
    ];

    /**
     * Map CSV headers to our database fields
     * Returns ALL matching indices for each field to enable summing of multiple columns
     *
     * @param array $headers Raw CSV headers
     * @return array Mapping of our field names to CSV column indices (array of indices per field)
     */
    public function mapHeaders(array $headers): array
    {
        $headerMap = [];

        foreach ($this->mappings as $ourField => $possibleHeaders) {
            $headerMap[$ourField] = $this->findAllHeaderIndices($headers, $possibleHeaders);
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
     * Find ALL indices of matching headers
     * Allows multiple columns to map to the same field for summing
     *
     * @param array $headers CSV headers
     * @param array $possibleNames Possible header names to match
     * @return array Array of indices for all matched headers
     */
    private function findAllHeaderIndices(array $headers, array $possibleNames): array
    {
        $indices = [];

        foreach ($headers as $index => $header) {
            $cleanHeader = trim($header, " \t\n\r\0\x0B'\"");

            foreach ($possibleNames as $possibleName) {
                if (strcasecmp($cleanHeader, $possibleName) === 0) {
                    $indices[] = $index;
                    break; // Move to next header after finding a match
                }
            }
        }

        return $indices;
    }

    /**
     * Extract values from CSV row using the header map
     * Sums all values from multiple columns mapping to the same field
     *
     * @param array $row CSV row data
     * @param array $headerMap Header mapping from mapHeaders()
     * @return array Extracted values with our field names (target, open, submitted, approved, rejected)
     */
    public function extractValues(array $row, array $headerMap): array
    {
        $values = [];

        foreach ($headerMap as $ourField => $csvIndices) {
            $sum = 0;

            // Sum all values from matching columns
            if (is_array($csvIndices) && count($csvIndices) > 0) {
                foreach ($csvIndices as $csvIndex) {
                    if (isset($row[$csvIndex])) {
                        $sum += $this->cleanValue($row[$csvIndex]);
                    }
                }
            }

            $values[$ourField] = $sum;
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
     * @param array $headerMap Header mapping (array of indices per field)
     * @param array $headers Original headers
     * @return string Description of detected format
     */
    public function getFormatDescription(array $headerMap, array $headers): string
    {
        $description = "Detected CSV format:\n";

        foreach ($headerMap as $ourField => $csvIndices) {
            if (is_array($csvIndices) && count($csvIndices) > 0) {
                $columnNames = array_map(fn($idx) => "'{$headers[$idx]}' (col {$idx})", $csvIndices);
                $description .= "  - {$ourField}: " . implode(', ', $columnNames) . " [will be summed]\n";
            } else {
                $description .= "  - {$ourField}: NOT FOUND\n";
            }
        }

        return $description;
    }
}
