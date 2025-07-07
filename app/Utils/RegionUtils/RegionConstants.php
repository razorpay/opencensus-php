<?php

namespace App\Utils\RegionUtils;

/**
 * RegionConstants - Centralized constants for region management
 *
 * This class contains all region-related constants to avoid hardcoded strings
 * and provide a single source of truth for region configuration values.
 */
class RegionConstants
{
    // ===== REGION CONSTANTS (2-letter country codes) =====
    // These constants are used for merchant regions
    public const REGION_INDIA = 'IN';
    public const REGION_SINGAPORE = 'SG';
    public const REGION_US = 'US';
    public const REGION_MALAYSIA = 'MY';

    // ===== CELL REGION CONSTANTS =====
    // These constants define the available cell regions
    public const CELL_REGION_INDIA = 'IN';
    public const CELL_REGION_SINGAPORE = 'SG';
    public const CELL_REGION_US = 'US';

    // Valid Merchant Regions Array
    public const VALID_MERCHANT_REGIONS = [
        self::REGION_INDIA,
        self::REGION_SINGAPORE,
        self::REGION_US,
        self::REGION_MALAYSIA,
    ];

    // Valid Cell Regions Array
    public const VALID_CELL_REGIONS = [
        self::CELL_REGION_INDIA,
        self::CELL_REGION_SINGAPORE,
        self::CELL_REGION_US,
    ];

    // Legacy: Keep for backward compatibility
    public const VALID_REGIONS = self::VALID_MERCHANT_REGIONS;

    /**
     * Merchant Region to Cell Region Mapping
     * 
     * This mapping defines which cell region serves each merchant region.
     * Multiple merchant regions can be served by the same cell region.
     * 
     * @var array<string, string>
     */
    public const MERCHANT_TO_CELL_MAPPING = [
        self::REGION_INDIA => self::CELL_REGION_INDIA,
        self::REGION_MALAYSIA => self::CELL_REGION_INDIA,  // MY merchants served by IN cell
        self::REGION_SINGAPORE => self::CELL_REGION_SINGAPORE,
        self::REGION_US => self::CELL_REGION_US,
    ];

    /**
     * Default region used as fallback when configuration is invalid
     * The actual default is configured in config/app.php
     */
    public const DEFAULT_REGION = self::REGION_INDIA;

    /**
     * Get the cell region that serves a given merchant region
     * 
     * @param string $merchantRegion The merchant region (e.g., 'IN', 'MY', 'SG', 'US')
     * @return string The cell region that serves this merchant region
     */
    public static function getMappedCellRegion(string $merchantRegion): string
    {
        return self::MERCHANT_TO_CELL_MAPPING[strtoupper($merchantRegion)] ?? self::DEFAULT_REGION;
    }

    /**
     * Check if a merchant region is served by the given cell region
     * 
     * @param string $merchantRegion The merchant region
     * @param string $cellRegion The cell region
     * @return bool True if the merchant region is served by the cell region
     */
    public static function isMerchantRegionServedByCellRegion(string $merchantRegion, string $cellRegion): bool
    {
        $mappedCellRegion = self::getMappedCellRegion($merchantRegion);
        return strtoupper($mappedCellRegion) === strtoupper($cellRegion);
    }
}
