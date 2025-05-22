import {
  ProcessedTest,
  TestCase,
  processFailedTests as processTests,
} from '../extractors/testExtractor';
import { NetworkCall } from '../extractors/networkExtractor';
import fs from 'fs';
import path from 'path';
import { DASHBOARD_ROOT } from '@src/constants';

/**
 * Extract microapp information from package.json
 */
function extractMicroappInfo(): { name: string; description: string; directory: string } {
  const workingDir = process.cwd();
  const defaultInfo = {
    name: 'unknown',
    description: 'No description available',
  };

  const packageJson = JSON.parse(fs.readFileSync(path.resolve(`${workingDir}/package.json`), 'utf8'));

  return {
    name: packageJson.name || defaultInfo.name,
    description: packageJson.description || defaultInfo.description,
    directory: path.relative(DASHBOARD_ROOT, workingDir),
  };
}

/**
 * Save the combined test and network analysis report to a JSON file
 */
export function saveCombinedReportToJson(
  grouped: any,
  failedTests: TestCase[],
  outputPath: string,
  metadata?: Record<string, any>
): void {
  if (!outputPath) {
    outputPath = path.join(process.cwd(), 'analysis-report.json');
  }

  try {
    console.log(`Saving comprehensive E2E report to ${outputPath}...`);

    // Ensure grouped has the expected structure
    if (!grouped) {
      grouped = {
        summary: { total: 0 },
        coreBundles: {},
        internal: [],
        external: {},
      };
    }

    // Ensure the summary property exists
    if (!grouped.summary) {
      grouped.summary = { total: 0 };
    }

    // Process network data
    processNetworkData(grouped);

    // Process failed tests for JSON output
    const processedFailedTests = processFailedTests(failedTests);

    // Extract microapp information
    const microappInfo = extractMicroappInfo();

    const outputJson = {
      meta: {
        timestamp: new Date().toISOString(),
        totalTests: failedTests ? failedTests.length : 0,
        totalCalls: grouped.summary.total || 0, // Add fallback for total
        schemaVersion: '1.1',
        description: 'Comprehensive E2E test analysis with network calls from Playwright traces',
        microapp: {
          name: microappInfo.name,
          description: microappInfo.description,
          directory: microappInfo.directory,
        },
        // Include custom metadata if provided
        ...(metadata || {})
      },
      tests: {
        failed: processedFailedTests,
        summary: {
          total: failedTests ? failedTests.length : 0,
        },
      },
      network: grouped,
    };

    fs.writeFileSync(outputPath, JSON.stringify(outputJson, null, 2));
    console.log(`Comprehensive E2E report saved to ${outputPath}`);
    console.log(`Included microapp info: ${microappInfo.name} (${microappInfo.directory})`);
    
    if (metadata) {
      console.log(`Included custom metadata: ${Object.keys(metadata).join(', ')}`);
    }
  } catch (error) {
    console.error(`Error saving report: ${(error as Error).message}`);
    console.error(`Error stack: ${(error as Error).stack}`);
  }
}

/**
 * Process network data for JSON output
 */
function processNetworkData(grouped: any): void {
  // Exit early if grouped is null or undefined
  if (!grouped) return;

  // Helper function to trim responses
  const ensureResponsesTrimmed = (calls: NetworkCall[]) => {
    if (!calls || !Array.isArray(calls)) return;

    for (const call of calls) {
      // Skip calls already marked for omission
      if (call.shouldOmitFromFinalJson) continue;

      processNetworkCall(call);
    }
  };

  // Process different types of network calls
  if (grouped.internal && Array.isArray(grouped.internal)) {
    ensureResponsesTrimmed(grouped.internal);
  }

  if (grouped.external) {
    Object.values(grouped.external).forEach((calls: any) => {
      if (Array.isArray(calls)) {
        ensureResponsesTrimmed(calls);
      }
    });
  }

  if (grouped.coreBundles) {
    Object.values(grouped.coreBundles)
      .filter((bundle: any) => bundle && bundle.calls && Array.isArray(bundle.calls))
      .forEach((bundle: any) => {
        ensureResponsesTrimmed(bundle.calls);
      });
  }

  if (grouped.unknown && Array.isArray(grouped.unknown)) {
    ensureResponsesTrimmed(grouped.unknown);
  }

  updateNetworkSummary(grouped);
}

/**
 * Process a single network call for JSON output
 */
function processNetworkCall(call: NetworkCall): void {
  // Skip if call is null or undefined
  if (!call) return;

  // Make sure every call has a response field
  if (!call.response) {
    // Try to derive a response from available data
    if (call.responseBody) {
      call.response =
        typeof call.responseBody === 'string' && call.responseBody.length > 250
          ? call.responseBody.substring(0, 247) + '...'
          : call.responseBody;
    } else if (call.responseBodyJson) {
      call.response = JSON.stringify(call.responseBodyJson);
    } else if (call.apiResponse) {
      call.response = JSON.stringify(call.apiResponse);
    } else if (call.responseBodySha1) {
      call.response = `[Response content SHA1: ${call.responseBodySha1}]`;
    } else if (call.responseBodySize) {
      call.response = `[Response size: ${call.responseBodySize} bytes]`;
    } else {
      call.response = '[No response data available]';
    }
  }

  // Ensure response is properly truncated if it's a string
  if (call.response && typeof call.response === 'string' && call.response.length > 250) {
    call.response = call.response.substring(0, 247) + '...';
  }

  // Clean up any leftover responseBody to save space
  delete call.responseBody;
  delete call.responseBodyJson;
  delete call.apiResponse;
}

/**
 * Update network summary statistics
 */
function updateNetworkSummary(grouped: any): void {
  if (!grouped.summary) {
    grouped.summary = {
      total: 0,
      successful: 0,
      failed: 0,
      byCategory: {
        coreBundles: 0,
        internal: 0,
        external: 0,
        unknown: 0,
      },
    };
  }

  // Ensure byCategory exists
  if (!grouped.summary.byCategory) {
    grouped.summary.byCategory = {
      coreBundles: 0,
      internal: 0,
      external: 0,
      unknown: 0,
    };
  }

  // Calculate new totals after filtering
  const newByCategory = {
    coreBundles: 0,
    internal: grouped.internal && Array.isArray(grouped.internal) ? grouped.internal.length : 0,
    external: 0,
    unknown: 0,
  };

  // Count external calls
  if (grouped.external) {
    Object.values(grouped.external).forEach((calls: any) => {
      if (Array.isArray(calls)) {
        newByCategory.external += calls.length;
      }
    });
  }

  // Process core bundles
  if (grouped.coreBundles) {
    Object.entries(grouped.coreBundles).forEach(([_, bundle]: [string, any]) => {
      if (!bundle || !bundle.calls || !Array.isArray(bundle.calls)) return;

      // Filter out calls marked for omission
      bundle.calls = bundle.calls.filter((call: NetworkCall) => !call.shouldOmitFromFinalJson);

      // Update counts
      bundle.successful = bundle.calls.filter((call: NetworkCall) => call.success).length;
      bundle.failed = bundle.calls.filter((call: NetworkCall) => !call.success).length;

      newByCategory.coreBundles += bundle.calls.length;
    });
  }

  // Process unknown category
  if (grouped.unknown && Array.isArray(grouped.unknown)) {
    grouped.unknown = grouped.unknown.filter((call: NetworkCall) => !call.shouldOmitFromFinalJson);
    newByCategory.unknown = grouped.unknown.length;
  }

  // Calculate new total and update summary
  const newTotal = Object.values(newByCategory).reduce((sum, count) => sum + count, 0);
  grouped.summary.total = newTotal;
  grouped.summary.byCategory = newByCategory;

  // Calculate successful and failed counts
  let successful = 0;
  let failed = 0;

  // Count from internal
  if (grouped.internal && Array.isArray(grouped.internal)) {
    successful += grouped.internal.filter((call: NetworkCall) => call.success).length;
    failed += grouped.internal.filter((call: NetworkCall) => !call.success).length;
  }

  // Count from external
  if (grouped.external) {
    Object.values(grouped.external).forEach((calls: any) => {
      if (Array.isArray(calls)) {
        successful += calls.filter((call: NetworkCall) => call.success).length;
        failed += calls.filter((call: NetworkCall) => !call.success).length;
      }
    });
  }

  // Count from coreBundles
  if (grouped.coreBundles) {
    Object.values(grouped.coreBundles).forEach((bundle: any) => {
      if (bundle && bundle.calls && Array.isArray(bundle.calls)) {
        successful += bundle.successful || 0;
        failed += bundle.failed || 0;
      }
    });
  }

  // Update counts
  grouped.summary.successful = successful;
  grouped.summary.failed = failed;

  console.log(`Final network report contains ${newTotal} calls after filtering 200 OK asset calls`);
}

/**
 * Process failed tests and prepare for JSON output
 */
function processFailedTests(failedTests: TestCase[]): ProcessedTest[] {
  if (!failedTests || failedTests.length === 0) {
    return [];
  }

  // Use the imported function from testExtractor
  return processTests(failedTests);
}
