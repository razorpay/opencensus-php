import type { Reporter } from '@playwright/test/reporter';
import path from 'path';
import fs from 'fs';
import {
  extractFailedTests,
  printTestDetails,
  extractNetworkDataFromTest,
  TestCase as TestCaseType,
} from './extractors';
import { saveCombinedReportToJson } from './transformers';

interface AnalysisResult {
  failedTests: TestCaseType[];
  networkData: any | null;
}

// Define an interface for reporter configuration options
interface ReporterOptions {
  analysisDir: string;
  fullReportJson: string;
}

class DashboardPlaywrightRefinedJson implements Reporter {
  private options: ReporterOptions;

  constructor(options: ReporterOptions) {
    if (!options.analysisDir) {
      throw new Error('analysisDir is required');
    }

    this.options = options;
  }

  onEnd(): void {
    // Using setTimeout to ensure this runs after other reporters have completed
    setTimeout(() => {

      try {
        // Check if directory exists
        if (fs.existsSync(this.options.analysisDir)) {
          // Read directory contents
          const files = fs.readdirSync(this.options.analysisDir);

          console.log(`Files and folders in ${this.options.analysisDir}:`);
          files.forEach((file) => {
            const filePath = path.join(this.options.analysisDir, file);
            const stats = fs.statSync(filePath);
            console.log(`- ${file} (${stats.isDirectory() ? 'directory' : 'file'})`);
          });

          if (fs.existsSync(this.options.fullReportJson)) {
            this.analyzePlaywrightReport();
          } else {
            console.log(`Report file not found at ${this.options.fullReportJson}`);
          }
        } else {
          console.log(`Directory ${this.options.analysisDir} does not exist`);
        }
      } catch (error) {
        console.error(`Error reading directory: ${error}`);
      }
    }, 0);
  }

  /**
   * Analyzes Playwright test report and extracts detailed information about failed tests
   */
  analyzePlaywrightReport(): AnalysisResult {
    const analysisDir = this.options.analysisDir;
    const reportPath = this.options.fullReportJson;
    const result: AnalysisResult = { failedTests: [], networkData: null };
    
    if (!fs.existsSync(reportPath)) {
      console.error(`Error: Report file not found at ${reportPath}`);
      return result;
    }

    try {
      const reportData = JSON.parse(fs.readFileSync(reportPath, 'utf8'));
      const failedTests = extractFailedTests(reportData);
      let networkData = null;

      if (failedTests.length === 0) {
        console.log('No failed tests found in the report.');
        return result;
      }

      console.log(`Found ${failedTests.length} failed tests:\n`);

      // Check if any test has trace attachments
      const hasTraces = failedTests.some(test => 
        test.attachments && test.attachments.some(a => a.name === 'trace')
      );

      if (!hasTraces) {
        console.log('No trace attachments found in failed tests. Network data will not be available.');
      } else {
        // Process each failed test for network data
        let successfulNetworkExtraction = false;
        
        for (let i = 0; i < failedTests.length; i++) {
          const test = failedTests[i];
          printTestDetails(test, i + 1);

          // Extract network data with basic options
          const extractOptions = { extractNetworkCalls: true };

          try {
            const testNetworkData = extractNetworkDataFromTest(test, extractOptions, networkData);
            
            if (testNetworkData) {
              networkData = testNetworkData;
              successfulNetworkExtraction = true;
              console.log(`Successfully extracted network data from test #${i + 1}`);
            }
          } catch (netError) {
            console.error(`Error extracting network data from test #${i + 1}: ${(netError as Error).message}`);
          }
        }

        if (!successfulNetworkExtraction) {
          console.warn('Failed to extract network data from any test. Network insights will be limited.');
        }
      }

      // Determine output path - use options.outputFile if provided, otherwise use default
      const outputPath = path.join(analysisDir, 'playwright-refined-report.json');
      
      // Create default network data structure
      const defaultNetworkData = {
        summary: { 
          total: 0,
          successful: 0,
          failed: 0,
          byCategory: {
            coreBundles: 0,
            internal: 0,
            external: 0,
            unknown: 0,
          }
        },
        coreBundles: {},
        internal: [],
        external: {},
      };

      // Enhanced logging for report generation
      console.log(`Generating refined report with${networkData ? '' : 'out'} network data...`);
      
      
      // Save the full report with test data, network data, and metadata
      saveCombinedReportToJson(
        networkData || defaultNetworkData, 
        failedTests, 
        outputPath,
      );
      
      console.log(`Report saved to ${outputPath}`);

      result.failedTests = failedTests;
      result.networkData = networkData;
      return result;
    } catch (error) {
      console.error(`Error processing report: ${(error as Error).message}`);
      console.error(`Error stack: ${(error as Error).stack}`);
      return result;
    }
  }
}

export default DashboardPlaywrightRefinedJson;
