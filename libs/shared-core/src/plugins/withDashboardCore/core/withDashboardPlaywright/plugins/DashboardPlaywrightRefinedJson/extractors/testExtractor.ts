import { formatDuration, extractTestSourceCode } from '../utils';

export interface TestError {
  message: string;
  location?: {
    file?: string;
    line?: number;
    column?: number;
  };
}

export interface TestResult {
  status: string;
  duration: number;
  error?: TestError;
  attachments?: TestAttachment[];
  stdout?: any[];
}

export interface TestAttachment {
  name: string;
  path: string;
  contentType?: string;
}

export interface TestCase {
  title: string;
  fullTitle: string;
  file: string;
  line: number;
  column: number;
  tags?: string[];
  error: TestError;
  duration: number;
  projectName: string;
  attachments?: TestAttachment[];
  stdout?: any[];
}

export interface Suite {
  title: string;
  suites: Suite[];
  specs: any[];
  allTests(): any[];
}

export interface ProcessedTest {
  title: string;
  fullTitle: string;
  file: string;
  tags?: string[];
  projectName: string;
  duration: string;
  error: {
    message: string;
    location?: {
      file: string;
    } | null;
    failingAt: string;
  };
  attachments: {
    name: string;
    path: string;
  }[];
}

/**
 * Extracts failed tests from the report data
 */
export function extractFailedTests(reportData: any): TestCase[] {
  const failedTests: TestCase[] = [];

  // Process all suites recursively
  function processSuite(suite: any, parentTitle = '') {
    // Process child suites
    if (suite.suites && suite.suites.length > 0) {
      suite.suites.forEach((childSuite: any) => {
        const fullTitle = parentTitle ? `${parentTitle} > ${childSuite.title}` : childSuite.title;
        processSuite(childSuite, fullTitle);
      });
    }

    // Process specs in the current suite
    if (suite.specs && suite.specs.length > 0) {
      suite.specs.forEach((spec: any) => {
        if (!spec.ok) {
          spec.tests.forEach((test: any) => {
            if (test.results && test.results.some((result: TestResult) => result.status === 'failed')) {
              const failedResult = test.results.find((result: TestResult) => result.status === 'failed');
              failedTests.push({
                title: spec.title,
                fullTitle: parentTitle ? `${parentTitle} > ${spec.title}` : spec.title,
                file: spec.file,
                line: spec.line,
                column: spec.column,
                tags: spec.tags || [],
                error: failedResult.error!,
                duration: failedResult.duration,
                projectName: test.projectName,
                attachments: failedResult.attachments || [],
                stdout: failedResult.stdout || [],
              });
            }
          });
        }
      });
    }
  }

  // Start processing from the top-level suites
  if (reportData.suites) {
    reportData.suites.forEach((suite: Suite) => processSuite(suite));
  }

  return failedTests;
}

/**
 * Prints details of a failed test
 */
export function printTestDetails(test: TestCase, index: number): void {
  console.log(`\n-------- Failed Test #${index} --------`);
  console.log(`Title: ${test.title}`);
  console.log(`Full Path: ${test.fullTitle}`);
  console.log(`File: ${test.file} (Line: ${test.line}, Column: ${test.column})`);
  console.log(`Project: ${test.projectName}`);
  console.log(`Duration: ${formatDuration(test.duration)}`);

  if (test.tags && test.tags.length > 0) {
    console.log(`Tags: ${test.tags.join(', ')}`);
  }

  console.log('\nError:');
  console.log(`  Message: ${test.error.message}`);

  if (test.error.location) {
    console.log(
      `  Location: ${test.error.location.file}:${test.error.location.line}:${test.error.location.column}`,
    );
  }

  // Add source code display - prefer error.location file path which is usually absolute
  const sourceFilePath = test.error.location?.file || test.file;
  const sourceLine = test.error.location?.line || test.line;

  console.log('\nSource Code:');
  console.log(extractTestSourceCode(sourceFilePath!, sourceLine!));

  if (test.stdout && test.stdout.length > 0) {
    console.log('\nStandard Output:');
    test.stdout.forEach((output) => {
      console.log(`  ${output.text.trim()}`);
    });
  }

  if (test.attachments && test.attachments.length > 0) {
    console.log('\nAttachments:');
    test.attachments.forEach((attachment) => {
      console.log(`  - ${attachment.name}: ${attachment.path}`);
    });
  }

  console.log('----------------------------------\n');
}

/**
 * Process failed tests and prepare them for JSON output
 */
export function processFailedTests(failedTests: TestCase[]): ProcessedTest[] {
  if (!failedTests || failedTests.length === 0) {
    return [];
  }

  // Get workspace root for making relative paths
  const workspaceRoot = process.cwd();
  const processedTests: ProcessedTest[] = [];

  for (const test of failedTests) {
    // Use error.location.file when available as it often contains the absolute path
    const sourceFilePath = test.error.location?.file || test.file;
    const sourceLine = test.error.location?.line || test.line;

    const formattedTest: ProcessedTest = {
      title: test.title,
      fullTitle: test.fullTitle,
      file: test.file,
      // Remove detailed location info (not needed in final output)
      tags: test.tags || [],
      projectName: test.projectName,
      // Add duration at the same level as title
      duration: formatDuration(test.duration),
      error: {
        message: test.error.message,
        // Include only necessary error information
        location: test.error.location
          ? {
              file: test.error.location.file!,
            }
          : null,
        // Move failingAt inside the error object
        failingAt: extractTestSourceCode(sourceFilePath!, sourceLine!),
      },
      attachments: [],
      // Remove failingAt from the top level
    };

    // Process attachments with relative paths but only include necessary info
    processAttachmentsMinimal(test, formattedTest, workspaceRoot);

    processedTests.push(formattedTest);
  }

  return processedTests;
}

/**
 * Process attachments with minimal information
 * @private
 */
function processAttachmentsMinimal(
  test: TestCase, 
  formattedTest: ProcessedTest, 
  workspaceRoot: string
): void {
  if (!test.attachments || !test.attachments.length) {
    return;
  }

  // Process only important attachments (screenshot, trace)
  test.attachments.forEach((attachment) => {
    if (attachment.name === 'screenshot' || attachment.name === 'trace') {
      const relativePath = require('path').relative(workspaceRoot, attachment.path);
      formattedTest.attachments.push({
        name: attachment.name,
        path: relativePath,
      });
    }
  });
} 