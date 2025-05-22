import { ASSET_MIME_TYPES } from './constants';
import fs from 'fs';
import path from 'path';

/**
 * Helper for cleanly truncating strings with ellipsis
 */
export function truncateString(str: string, maxLength: number): string {
  if (str.length <= maxLength) return str;
  return `${str.substring(0, maxLength - 3)}...`;
}

/**
 * Checks if a MIME type corresponds to an asset type that should be treated specially
 */
export function isAssetMimeType(mimeType: string): boolean {
  if (!mimeType) return false;

  // Convert to lowercase for case-insensitive comparison
  const lowerMimeType = mimeType.toLowerCase();

  return ASSET_MIME_TYPES.some((type) => lowerMimeType.includes(type));
}

/**
 * Filter headers based on whitelist
 */
export function filterHeaders(headers: Record<string, string>, whitelist: string[]): Record<string, string> {
  if (!headers || typeof headers !== 'object') {
    return {};
  }

  const filteredHeaders: Record<string, string> = {};

  // Convert all header names to lowercase for case-insensitive comparison
  Object.keys(headers).forEach((key) => {
    const lowerKey = key.toLowerCase();
    if (whitelist.includes(lowerKey)) {
      filteredHeaders[key] = headers[key];
    }
  });

  return filteredHeaders;
}

/**
 * Formats test duration into human-readable format
 */
export function formatDuration(ms: number): string {
  if (ms < 1000) {
    return `${ms}ms`;
  }

  const seconds = Math.floor(ms / 1000);
  const minutes = Math.floor(seconds / 60);

  if (minutes > 0) {
    const remainingSeconds = seconds % 60;
    return `${minutes}m ${remainingSeconds}s`;
  }

  return `${seconds}s`;
}

/**
 * Categorize URL based on the patterns specified
 */
export function categorizeUrl(url: string): { type: string; microapp?: string; domain?: string } {
  try {
    const urlObj = new URL(url);

    // Check if it's a core-bundles URL (CDN assets)
    const coreBundlesMatch = url.match(
      /(?:dashboard\.dev\.razorpay\.in|dashboard-base\.dev\.razorpay\.in)\/dashboard\/core-bundles\/([^/]+)/,
    );
    if (coreBundlesMatch) {
      return {
        type: 'core-bundles',
        microapp: coreBundlesMatch[1],
      };
    }

    // Check if it's an internal domain (includes "razorpay")
    if (urlObj.hostname.includes('razorpay')) {
      return {
        type: 'internal',
        domain: urlObj.hostname,
      };
    }

    // External URL (everything else)
    return {
      type: 'external',
      domain: urlObj.hostname,
    };
  } catch (e) {
    return {
      type: 'unknown',
    };
  }
}

/**
 * Create a simplified version of complex objects for storage
 */
export function createSimplifiedObject(obj: any): any {
  if (!obj || typeof obj !== 'object') {
    return obj;
  }

  // Handle arrays
  if (Array.isArray(obj)) {
    if (obj.length <= 2) {
      return obj.map(createSimplifiedObject);
    }

    // For larger arrays, just show length and first item
    return {
      _type: 'array',
      _length: obj.length,
      _sample: createSimplifiedObject(obj[0]),
    };
  }

  // Handle objects
  const keys = Object.keys(obj);
  if (keys.length <= 3) {
    // For small objects, include all properties with simplified values
    const result: Record<string, any> = {};
    keys.forEach((key) => {
      const value = obj[key];
      if (typeof value === 'object' && value !== null) {
        result[key] = createSimplifiedObject(value);
      } else if (typeof value === 'string' && value.length > 50) {
        result[key] = truncateString(value, 50);
      } else {
        result[key] = value;
      }
    });
    return result;
  }

  // For larger objects, show property count and first few keys
  const result: Record<string, any> = {
    _type: 'object',
    _properties: keys.length,
    _sample: {},
  };

  // Add first few keys to the sample
  keys.slice(0, 3).forEach((key) => {
    const value = obj[key];
    if (typeof value === 'object' && value !== null) {
      result._sample[key] = createSimplifiedObject(value);
    } else if (typeof value === 'string' && value.length > 50) {
      result._sample[key] = truncateString(value, 50);
    } else {
      result._sample[key] = value;
    }
  });

  return result;
}

/**
 * Extracts source code for a failing test
 */
export function extractTestSourceCode(filePath: string, line: number, contextLines = 5): string {
  if (!filePath) {
    return 'No file path provided';
  }

  // Check if this is an absolute path
  const isAbsolutePath = path.isAbsolute(filePath);
  const possiblePaths: string[] = [];

  // Use the absolute path if provided
  if (isAbsolutePath) {
    possiblePaths.push(filePath);
  } else {
    // Get workspace root (the dashboard directory)
    const workspaceRoot = process.cwd();

    // Try different path combinations
    possiblePaths.push(
      path.join(workspaceRoot, filePath),
      path.join(workspaceRoot, 'apps/self-serve/e2e/suites', filePath),
      path.join(workspaceRoot, 'e2e/suites', filePath),
      path.join(workspaceRoot, 'apps/self-serve', filePath),
    );
  }

  let resolvedPath = null;
  for (const pathToTry of possiblePaths) {
    if (fs.existsSync(pathToTry)) {
      resolvedPath = pathToTry;
      break;
    }
  }

  if (!resolvedPath) {
    console.warn(
      `Could not find source file: ${filePath} after trying paths: ${possiblePaths.join(', ')}`,
    );
    return `File not found: ${filePath}`;
  }

  try {
    const fileContent = fs.readFileSync(resolvedPath, 'utf8');
    const lines = fileContent.split('\n');

    // Calculate start and end lines with context
    const startLine = Math.max(0, line - contextLines - 1);
    const endLine = Math.min(lines.length - 1, line + contextLines - 1);

    // Extract the code with line numbers
    const sourceLines: string[] = [];
    for (let i = startLine; i <= endLine; i++) {
      const linePrefix = i === line - 1 ? '> ' : '  '; // Mark the error line with >
      sourceLines.push(`${linePrefix}${i + 1}: ${lines[i]}`);
    }

    return sourceLines.join('\n');
  } catch (error) {
    return `Error reading source code: ${(error as Error).message}`;
  }
} 