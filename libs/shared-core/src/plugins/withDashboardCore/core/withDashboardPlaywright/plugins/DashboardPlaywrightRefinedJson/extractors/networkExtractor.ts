import fs from 'fs';
import path from 'path';
import { execSync } from 'child_process';
import { TestCase } from './testExtractor';
import { 
  isAssetMimeType, 
  filterHeaders, 
  WHITELISTED_REQUEST_HEADERS, 
  WHITELISTED_RESPONSE_HEADERS, 
  categorizeUrl,
  createSimplifiedObject,
  truncateString
} from '../utils';

// Try to use AdmZip if available
let AdmZip: any;
try {
  // Dynamic import since this is TypeScript
  AdmZip = require('adm-zip');
} catch (e) {
  // Throw error instead of just warning
  throw new Error(
    'Error: adm-zip package is required for network extraction. ' +
    'Please run: npm install adm-zip'
  );
}

export interface NetworkCall {
  url: string;
  method: string;
  requestId?: string;
  requestHeaders: Record<string, string>;
  responseHeaders: Record<string, string>;
  statusCode?: number;
  statusText?: string;
  requestData?: any;
  responseBody?: string;
  responseBodyJson?: any;
  apiResponse?: any;
  responseBodySha1?: string;
  responseBodySize?: number;
  responseBodyMimeType?: string;
  response?: string;
  timestamp?: number;
  timing?: number;
  category?: {
    type: string;
    microapp?: string;
    domain?: string;
  };
  success?: boolean;
  shouldOmitFromFinalJson?: boolean;
  responseBodyIsAsset?: boolean;
  responseBodyAssetType?: string;
  responseBodyMissing?: boolean;
  responseBodyJsonParseError?: boolean;
  responseBodyJsonDetected?: boolean;
  responseBodyReadError?: boolean;
}

export interface NetworkOptions {
  extractNetworkCalls?: boolean;
  debug?: boolean;
  raw?: boolean;
  includeResponseBody?: boolean;
}

/**
 * Extract network data from a test if it has a trace attachment
 */
export function extractNetworkDataFromTest(test: TestCase, options: NetworkOptions, existingNetworkData: any): any {
  // If not extracting network calls or test has no attachments, return existing data
  if (!options.extractNetworkCalls || !test.attachments) {
    return existingNetworkData;
  }

  // If we already have network data, don't extract more
  if (existingNetworkData) {
    return existingNetworkData;
  }

  const traceAttachment = test.attachments?.find((a) => a.name === 'trace');
  if (!traceAttachment) {
    console.log('\nNo trace file found for this test.');
    return null;
  }

  // Check if trace file exists
  if (!fs.existsSync(traceAttachment.path)) {
    console.error(`Trace file not found at path: ${traceAttachment.path}`);
    return null;
  }

  console.log('\nExtracting Network Calls...');
  try {
    // AdmZip availability is already checked at the module level
    const networkCalls = extractNetworkCalls(traceAttachment.path, options);
    
    if (!networkCalls || networkCalls.length === 0) {
      console.log('No network calls found in the trace.');
      return null;
    }
    
    return printNetworkCalls(networkCalls);
  } catch (error) {
    console.error(`Error extracting network calls: ${(error as Error).message}`);
    console.error(`Error stack: ${(error as Error).stack}`);
    return null;
  }
}

/**
 * Extracts network calls from a trace file
 */
export function extractNetworkCalls(tracePath: string, options: NetworkOptions = {}): NetworkCall[] {
  if (!fs.existsSync(tracePath)) {
    throw new Error(`Trace file not found: ${tracePath}`);
  }

  // AdmZip availability is already checked at the module level
  const debug = options.debug || false;
  const includeResponseBody = options.includeResponseBody !== false; // Default to true

  // Create a temporary directory for extraction
  const tempDir = path.join(path.dirname(tracePath), '.temp_trace_extract');
  if (fs.existsSync(tempDir)) {
    try {
      execSync(`rm -rf "${tempDir}"`);
    } catch (error) {
      console.warn(`Warning: Failed to clean up temporary directory: ${(error as Error).message}`);
    }
  }

  fs.mkdirSync(tempDir, { recursive: true });

  // Extract the trace zip file
  try {
    const zip = new AdmZip(tracePath);
    zip.extractAllTo(tempDir, true);

    if (debug) {
      console.log(`\nDebug: Extracted trace files to ${tempDir}`);
      const files = fs.readdirSync(tempDir);
      console.log(`Debug: Found ${files.length} files in trace archive:`);
      files.forEach((file) => console.log(`  - ${file}`));
    }
  } catch (error) {
    throw new Error(`Failed to extract trace file: ${(error as Error).message}`);
  }

  // Playwright trace structure has changed over versions
  // Let's try different approaches to find network data
  const networkCalls: NetworkCall[] = [];
  
  try {
    // Enhanced extraction logic
    const resourcesDir = path.join(tempDir, 'resources');
    const resourceFiles = fs.existsSync(resourcesDir) ? fs.readdirSync(resourcesDir) : [];
    const resourcesIndex = new Map<string, string>();
    
    // Index resources for faster lookups
    if (resourceFiles.length > 0) {
      console.log(`Found ${resourceFiles.length} resource files which may contain response bodies`);
      
      resourceFiles.forEach((file) => {
        const sha1Match = file.match(/sha1-([a-f0-9]+)/i);
        if (sha1Match && sha1Match[1]) {
          resourcesIndex.set(sha1Match[1], path.join(resourcesDir, file));
        }
      });
      
      if (resourcesIndex.size > 0) {
        console.log(`Indexed ${resourcesIndex.size} resources by SHA1 hash`);
      }
    }
    
    // Function to get resource content by SHA1
    const getResourceContent = (sha1: string): string | null => {
      if (!sha1) return null;
      
      // Try direct match from index
      if (resourcesIndex.has(sha1)) {
        try {
          return fs.readFileSync(resourcesIndex.get(sha1)!, 'utf8');
        } catch (e) {
          // Fall back to searching
        }
      }
      
      // Search for file containing the SHA1
      if (resourceFiles.length > 0) {
        const resourceFile = resourceFiles.find((file) => file.includes(sha1));
        if (resourceFile) {
          try {
            return fs.readFileSync(path.join(resourcesDir, resourceFile), 'utf8');
          } catch (e) {
            console.warn(`Failed to read resource file for ${sha1}: ${(e as Error).message}`);
          }
        }
      }
      
      return null;
    };
    
    // First look for HAR files - they have the most complete data
    const harFiles = fs.readdirSync(tempDir).filter((file) => file.endsWith('.har'));
    if (harFiles.length > 0) {
      console.log(`Found ${harFiles.length} HAR files with network data`);
      
      harFiles.forEach((harFile) => {
        try {
          const harPath = path.join(tempDir, harFile);
          const harData = JSON.parse(fs.readFileSync(harPath, 'utf8'));
          const harNetwork = extractNetworkFromHar(harData);
          networkCalls.push(...harNetwork);
        } catch (harError) {
          console.warn(`Warning: Failed to process HAR file ${harFile}: ${(harError as Error).message}`);
        }
      });
    }
    
    // Check for network files which contain request/response data
    const networkFiles = fs.readdirSync(tempDir).filter((file) => file.endsWith('.network'));
    if (networkFiles.length > 0) {
      console.log(`Found ${networkFiles.length} network log files`);
      
      // Process each network file
      for (const networkFile of networkFiles) {
        const networkFilePath = path.join(tempDir, networkFile);
        try {
          const networkData = extractNetworkFromNetworkFile(
            networkFilePath, 
            includeResponseBody
          );
          
          // Process resources for network data
          processResourcesForNetworkData(
            networkData, 
            resourceFiles, 
            getResourceContent
          );
          
          networkCalls.push(...networkData);
        } catch (error) {
          console.warn(`Warning: Failed to process network file ${networkFile}: ${(error as Error).message}`);
        }
      }
    }
    
    // Look for trace files which might have network events
    const traceFiles = fs.readdirSync(tempDir).filter((file) => 
      file.endsWith('.trace') || file.endsWith('.ndjson')
    );
    
    if (traceFiles.length > 0) {
      for (const traceFile of traceFiles) {
        const traceFilePath = path.join(tempDir, traceFile);
        try {
          const events = extractNetworkEventsFromNdjson(traceFilePath, debug);
          if (events.length > 0) {
            // Process resources for trace events
            processResourcesForNetworkData(events, resourceFiles, getResourceContent);
            networkCalls.push(...events);
          }
        } catch (error) {
          console.warn(`Warning: Failed to process trace file ${traceFile}: ${(error as Error).message}`);
        }
      }
    }
    
    // Deduplicate network calls by URL and method
    const seen = new Set<string>();
    const uniqueCalls: NetworkCall[] = [];
    
    networkCalls.forEach((call) => {
      const key = `${call.method}:${call.url}`;
      if (!seen.has(key)) {
        seen.add(key);
        uniqueCalls.push(call);
      } else {
        // If we already have this call, check if the new one has more data
        const existingCall = uniqueCalls.find((c) => `${c.method}:${c.url}` === key);
        if (existingCall) {
          // Prefer calls with response body
          if (!existingCall.responseBody && call.responseBody) {
            // Copy response data to the existing call
            existingCall.responseBody = call.responseBody;
            existingCall.responseBodyJson = call.responseBodyJson;
            existingCall.statusCode = call.statusCode || existingCall.statusCode;
            existingCall.responseHeaders = call.responseHeaders || existingCall.responseHeaders;
          }
        }
      }
    });
    
    // Clean up temporary directory if not in raw debug mode
    if (!options.raw) {
      try {
        execSync(`rm -rf "${tempDir}"`);
      } catch (error) {
        console.warn(`Warning: Failed to clean up temporary directory: ${(error as Error).message}`);
      }
    } else {
      console.log(`\nDebug: Preserved temporary directory at ${tempDir} for inspection`);
    }
    
    // Log summary of extracted data
    const apiCalls = uniqueCalls.filter((call) =>
      call.url.includes('dashboard.dev.razorpay.in/merchant/api'),
    );
    
    const apiCallsWithResponse = apiCalls.filter(
      (call) => call.responseBody || call.responseBodyJson,
    );
    
    console.log(
      `Extracted ${uniqueCalls.length} unique network calls`,
    );
    console.log(
      `Found ${apiCalls.length} API calls, ${apiCallsWithResponse.length} with response data`,
    );
    
    return uniqueCalls;
  } catch (error) {
    console.error(`Error in network extraction: ${(error as Error).message}`);
    return networkCalls;
  }
}

/**
 * Extract network calls from HAR file
 */
function extractNetworkFromHar(harData: any): NetworkCall[] {
  const networkCalls: NetworkCall[] = [];

  if (harData && harData.log && Array.isArray(harData.log.entries)) {
    harData.log.entries.forEach((entry: any) => {
      if (entry.request && entry.response) {
        const networkCall: NetworkCall = {
          url: entry.request.url,
          method: entry.request.method,
          requestHeaders: {},
          responseHeaders: {},
          statusCode: entry.response.status,
          statusText: entry.response.statusText,
          timing: entry.time,
        };

        // Process request headers
        if (Array.isArray(entry.request.headers)) {
          const rawHeaders: Record<string, string> = {};
          entry.request.headers.forEach((header: any) => {
            rawHeaders[header.name] = header.value;
          });
          networkCall.requestHeaders = filterHeaders(rawHeaders, WHITELISTED_REQUEST_HEADERS);
        }

        // Process response headers
        if (Array.isArray(entry.response.headers)) {
          const rawHeaders: Record<string, string> = {};
          entry.response.headers.forEach((header: any) => {
            rawHeaders[header.name] = header.value;
          });
          networkCall.responseHeaders = filterHeaders(rawHeaders, WHITELISTED_RESPONSE_HEADERS);
        }

        // Process request body
        if (entry.request.postData) {
          networkCall.requestData = entry.request.postData.text;
        }

        // Process response body
        if (entry.response.content && entry.response.content.text) {
          networkCall.responseBody = entry.response.content.text;
          
          // Try to parse JSON
          if (entry.response.content.mimeType?.includes('application/json')) {
            try {
              networkCall.responseBodyJson = JSON.parse(entry.response.content.text);
            } catch (e) {
              // Keep original if parsing fails
            }
          }
        }
        
        // Add category
        networkCall.category = categorizeUrl(networkCall.url);
        
        // Add success flag
        networkCall.success = networkCall.statusCode! >= 200 && networkCall.statusCode! < 400;

        networkCalls.push(networkCall);
      }
    });
  }

  return networkCalls;
}

/**
 * Extract network calls from Playwright .network file
 */
function extractNetworkFromNetworkFile(filePath: string, includeResponseBody = true): NetworkCall[] {
  if (!fs.existsSync(filePath)) {
    return [];
  }

  try {
    const content = fs.readFileSync(filePath, 'utf8');
    const networkCalls: NetworkCall[] = [];
    const lines = content.split('\n').filter(Boolean);

    // Check for response body availability in the file
    const hasResponseContent =
      content.includes('"content":') &&
      (content.includes('"text":') || content.includes('"_sha1":'));

    console.log(
      `Processing network file with ${lines.length} entries. Response content available: ${hasResponseContent}`,
    );

    for (const line of lines) {
      try {
        // Parse the snapshot
        const resourceSnapshot = JSON.parse(line);
        if (resourceSnapshot.type !== 'resource-snapshot' || !resourceSnapshot.snapshot) {
          continue;
        }

        const snapshot = resourceSnapshot.snapshot;
        
        // Only include HTTP/HTTPS requests
        const isHttpRequest =
          snapshot.request?.url &&
          (snapshot.request.url.startsWith('http://') || snapshot.request.url.startsWith('https://'));

        if (!isHttpRequest) {
          continue;
        }
        
        // Create a network call object
        const networkCall: NetworkCall = {
          url: snapshot.request.url,
          method: snapshot.request.method,
          requestHeaders: {},
          responseHeaders: {},
          statusCode: snapshot.response ? snapshot.response.status : null,
          statusText: snapshot.response ? snapshot.response.statusText : null,
          timing: calculateTiming(snapshot.timings),
        };

        // Process request headers
        if (snapshot.request.headers) {
          const rawHeaders: Record<string, string> = {};
          if (Array.isArray(snapshot.request.headers)) {
            snapshot.request.headers.forEach((header: any) => {
              if (header.name && header.value) {
                rawHeaders[header.name] = header.value;
              }
            });
          } else {
            Object.assign(rawHeaders, snapshot.request.headers);
          }
          
          networkCall.requestHeaders = filterHeaders(rawHeaders, WHITELISTED_REQUEST_HEADERS);
        }

        // Process response headers
        if (snapshot.response?.headers) {
          const rawHeaders: Record<string, string> = {};
          if (Array.isArray(snapshot.response.headers)) {
            snapshot.response.headers.forEach((header: any) => {
              if (header.name && header.value) {
                rawHeaders[header.name] = header.value;
              }
            });
          } else {
            Object.assign(rawHeaders, snapshot.response.headers);
          }
          
          networkCall.responseHeaders = filterHeaders(rawHeaders, WHITELISTED_RESPONSE_HEADERS);
        }
        
        // Process request data
        if (snapshot.request.postData) {
          networkCall.requestData = snapshot.request.postData;
          
          // Try to parse JSON request data
          const contentType = networkCall.requestHeaders['content-type'] || '';
          const isJsonContent =
            contentType.includes('application/json') && 
            typeof snapshot.request.postData === 'string';

          if (isJsonContent) {
            try {
              networkCall.requestData = JSON.parse(snapshot.request.postData);
            } catch (e) {
              // Keep original if parsing fails
            }
          }
        }

        // Process response if available
        if (includeResponseBody && snapshot.response && snapshot.response.content) {
          const content = snapshot.response.content;
          
          // Store mime type information
          if (content.mimeType) {
            networkCall.responseBodyMimeType = content.mimeType;
          }
          
          // Get content type from headers
          let contentType = '';
          if (networkCall.responseHeaders) {
            const contentTypeKey = Object.keys(networkCall.responseHeaders).find(
              (key) => key.toLowerCase() === 'content-type',
            );
            
            if (contentTypeKey) {
              contentType = networkCall.responseHeaders[contentTypeKey];
            }
          }
          
          // Determine if asset based on mime type or content type header
          const isAssetType = 
            (content.mimeType && isAssetMimeType(content.mimeType)) || 
            (contentType && isAssetMimeType(contentType));
          
          // For 200 assets, mark for omission
          if (isAssetType && networkCall.statusCode === 200) {
            networkCall.shouldOmitFromFinalJson = true;
          }
          
          // Extract text content
          if (content.text) {
            networkCall.responseBody = typeof content.text === 'string' && content.text.length > 250
              ? truncateString(content.text, 250)
              : content.text;
              
            // Try to parse JSON responses
            if (contentType.includes('application/json') || 
                networkCall.url.includes('dashboard.dev.razorpay.in/merchant/api')) {
              try {
                networkCall.responseBodyJson = JSON.parse(content.text);
                
                // For dashboard PHP APIs, store response separately
                if (networkCall.url.includes('dashboard.dev.razorpay.in/merchant/api')) {
                  networkCall.apiResponse = createSimplifiedObject(networkCall.responseBodyJson);
                }
              } catch (e) {
                // Keep original if parsing fails
                networkCall.responseBodyJsonParseError = true;
              }
            }
          } 
          // Store SHA1 reference for later resolution
          else if (content._sha1) {
            networkCall.responseBodySha1 = content._sha1;
            networkCall.responseBodySize = content.size;
          }
        }
        
        // Add category
        networkCall.category = categorizeUrl(networkCall.url);
        
        // Add success flag
        networkCall.success = networkCall.statusCode! >= 200 && networkCall.statusCode! < 400;

        networkCalls.push(networkCall);
      } catch (error) {
        // Skip invalid entries
      }
    }

    return networkCalls;
  } catch (error) {
    console.warn(`Warning: Failed to extract from network file: ${(error as Error).message}`);
    return [];
  }
}

/**
 * Calculate timing from timings object
 */
function calculateTiming(timings: any): number | undefined {
  if (!timings) return undefined;
  return timings.wait + (timings.receive || 0) + (timings.connect || 0) + (timings.send || 0);
}

/**
 * Extracts network events from an NDJSON trace file
 */
function extractNetworkEventsFromNdjson(filePath: string, debug = false): NetworkCall[] {
  if (!fs.existsSync(filePath)) {
    return [];
  }

  const networkCalls: NetworkCall[] = [];
  const content = fs.readFileSync(filePath, 'utf8');
  const lines = content.split('\n').filter(Boolean);

  if (debug) {
    console.log(`Debug: Parsing ${lines.length} lines from ${path.basename(filePath)}`);
  }

  // Quick check if file contains network-related keywords
  const hasNetworkData = /Network\.|fetch|request|XHR|resource|http[s]?:\/\//.test(content);
  if (!hasNetworkData) {
    if (debug) {
      console.log(`Debug: No network-related keywords found in ${path.basename(filePath)}`);
    }
    return [];
  }

  // Process each line as a separate JSON object
  for (let index = 0; index < lines.length; index++) {
    const line = lines[index];
    let event;

    try {
      event = JSON.parse(line);
    } catch (e) {
      // Skip invalid JSON
      continue;
    }

    // Look for different network event formats
    const isNetworkEvent =
      event.type === 'resource' ||
      event.method === 'apiRequestContext.fetch' ||
      (event.metadata &&
        event.metadata.method &&
        (event.metadata.method.startsWith('Network.') ||
          event.metadata.method === 'apiRequestContext.fetch'));

    if (!isNetworkEvent) {
      continue;
    }

    // Extract API call details
    if (event.method === 'apiRequestContext.fetch' && event.params) {
      const call: NetworkCall = {
        url: event.params.url,
        method: event.params.method || 'GET',
        requestHeaders: event.params.headers || {},
        responseHeaders: {},
        timestamp: event.timestamp,
      };

      // Look for corresponding response in subsequent lines
      for (let i = index + 1; i < Math.min(index + 200, lines.length); i++) {
        try {
          const responseEvent = JSON.parse(lines[i]);
          const isMatchingResponse =
            responseEvent.type === 'resource-snapshot' &&
            responseEvent.snapshot &&
            responseEvent.snapshot.request &&
            responseEvent.snapshot.request.url === call.url;

          if (isMatchingResponse) {
            call.responseHeaders = responseEvent.snapshot.response?.headers || {};
            call.statusCode = responseEvent.snapshot.response?.status;
            call.responseBody = responseEvent.snapshot.response?.content;
            
            // Try to parse JSON
            if (call.responseBody && typeof call.responseBody === 'string') {
              try {
                call.responseBodyJson = JSON.parse(call.responseBody);
              } catch (e) {
                // Keep original
              }
            }
            
            break;
          }
        } catch (e) {
          // Skip invalid JSON
        }
      }
      
      // Add category and success flag
      call.category = categorizeUrl(call.url);
      call.success = call.statusCode ? call.statusCode >= 200 && call.statusCode < 400 : undefined;
      
      networkCalls.push(call);
      continue;
    }

    // Handle Chrome DevTools Protocol network events
    if (event.metadata && event.metadata.method) {
      const { method, params } = event.metadata;

      if (method === 'Network.requestWillBeSent' && params && params.request) {
        const requestId = params.requestId;
        const call: NetworkCall = {
          requestId,
          url: params.request.url,
          method: params.request.method,
          requestHeaders: params.request.headers || {},
          responseHeaders: {},
          timestamp: event.timestamp,
        };
        
        // Add to collection - we'll match responses later
        networkCalls.push(call);
      } else if (method === 'Network.responseReceived' && params) {
        const requestId = params.requestId;
        const existingCall = networkCalls.find((c) => c.requestId === requestId);

        if (existingCall) {
          existingCall.responseHeaders = params.response?.headers || {};
          existingCall.statusCode = params.response?.status;
          existingCall.statusText = params.response?.statusText;
          existingCall.responseBodyMimeType = params.response?.mimeType;
          
          // Add category and success flag
          existingCall.category = categorizeUrl(existingCall.url);
          existingCall.success = existingCall.statusCode ? 
            existingCall.statusCode >= 200 && existingCall.statusCode < 400 : 
            undefined;
        }
      }
    }
  }

  return networkCalls;
}

/**
 * Process resources for network data
 */
function processResourcesForNetworkData(
  networkData: NetworkCall[], 
  resourceFiles: string[], 
  getResourceContent: (sha1: string) => string | null
): void {
  if (resourceFiles.length === 0) return;

  networkData.forEach((call) => {
    if (!call.responseBody && call.responseBodySha1) {
      const resourceContent = getResourceContent(call.responseBodySha1);
      if (resourceContent) {
        call.responseBody = resourceContent;

        // Try to parse JSON response
        const contentType = call.responseHeaders['content-type'] || '';
        if (contentType.includes('application/json')) {
          try {
            call.responseBodyJson = JSON.parse(resourceContent);
            
            // For dashboard API calls, store API response separately
            if (call.url.includes('dashboard.dev.razorpay.in/merchant/api')) {
              call.apiResponse = createSimplifiedObject(call.responseBodyJson);
            }
          } catch (e) {
            // Keep original if parsing fails
            call.responseBodyJsonParseError = true;
          }
        }
      }
    }
  });
}

/**
 * Print network call details and return the grouped data
 */
export function printNetworkCalls(networkCalls: NetworkCall[]): any {
  if (!networkCalls || networkCalls.length === 0) {
    console.log('No network calls found in the trace.');
    return {
      summary: { 
        total: 0, 
        successful: 0, 
        failed: 0,
        byCategory: {
          coreBundles: 0,
          internal: 0,
          external: 0,
          unknown: 0,
        },
      },
      coreBundles: {}, // Empty object - will be populated dynamically based on actual microapps
      internal: [],
      external: {}, // Will now store only statistics for each domain
    };
  }

  // Add categorization and success flag to each call
  networkCalls.forEach((call) => {
    if (!call.category) {
      call.category = categorizeUrl(call.url);
    }
    
    if (call.success === undefined) {
      call.success = call.statusCode ? call.statusCode >= 200 && call.statusCode < 400 : true;
    }
  });

  // Define the structure for domain statistics
  interface DomainStats {
    total: number;
    successful: number;
    failed: number;
  }

  // Group the network calls by category
  const grouped = {
    coreBundles: {} as Record<string, any>,
    internal: [] as NetworkCall[],
    external: {} as Record<string, DomainStats>,
    summary: {
      total: networkCalls.length,
      successful: networkCalls.filter((call) => call.success).length,
      failed: networkCalls.filter((call) => !call.success).length,
      byCategory: {
        coreBundles: 0,
        internal: 0,
        external: 0,
        unknown: 0,
      },
    },
  };

  // Category counters for accurate tracking
  const categoryCounters = {
    coreBundles: 0,
    internal: 0,
    external: 0,
    unknown: 0,
  };

  // Group calls into their respective categories
  networkCalls.forEach((call) => {
    const category = call.category!;

    // For core-bundles, only keep non-200 responses
    if (category.type === 'core-bundles' && call.statusCode === 200) {
      call.shouldOmitFromFinalJson = true;
    }

    if (category.type === 'core-bundles') {
      const microapp = category.microapp!;
      // Dynamic creation of microapp entries in the core bundles object
      // This ensures we only create entries for microapps that actually appear in the network calls
      if (!grouped.coreBundles[microapp]) {
        grouped.coreBundles[microapp] = {
          failedNetworkCallDetails: [],
          successful: 0,
          failed: 0,
        };
      }

      // Track all calls for statistics
      const bundle = grouped.coreBundles[microapp];
      if (call.success) {
        bundle.successful++;
      } else {
        bundle.failed++;
        
        // Only add failed calls to the details array
        if (!call.shouldOmitFromFinalJson) {
          bundle.failedNetworkCallDetails.push(call);
        }
      }

      categoryCounters.coreBundles++; 
    } else if (category.type === 'internal') {
      // For internal domains, keep all calls but ensure responses are trimmed
      if (
        call.responseBody &&
        typeof call.responseBody === 'string' &&
        call.responseBody.length > 250
      ) {
        call.responseBody = truncateString(call.responseBody, 250);
      }

      grouped.internal.push(call);
      categoryCounters.internal++;
    } else if (category.type === 'external') {
      const domain = category.domain!;
      
      // Initialize domain stats if this is the first call for this domain
      if (!grouped.external[domain]) {
        grouped.external[domain] = {
          total: 0,
          successful: 0,
          failed: 0
        };
      }
      
      // Update domain statistics
      const domainStats = grouped.external[domain];
      domainStats.total++;
      
      if (call.success) {
        domainStats.successful++;
      } else {
        domainStats.failed++;
      }

      categoryCounters.external++;
    } else {
      categoryCounters.unknown++;
    }
  });

  // Update the summary with accurate category counts
  grouped.summary.byCategory = categoryCounters;

  // Calculate totals for verification
  let coreBundleTotal = 0;
  Object.values(grouped.coreBundles).forEach(bundle => {
    coreBundleTotal += bundle.successful + bundle.failed;
  });

  // Check if numbers match and log warning if not
  if (coreBundleTotal !== categoryCounters.coreBundles) {
    console.warn(`Warning: Core bundle counts might be inconsistent. Calculated: ${coreBundleTotal}, Tracked: ${categoryCounters.coreBundles}`);
  }

  const internalTotal = grouped.internal.length;
  if (internalTotal !== categoryCounters.internal) {
    console.warn(`Warning: Internal API counts might be inconsistent. Found: ${internalTotal}, Tracked: ${categoryCounters.internal}`);
  }

  // For external, calculate total from domain stats
  let externalTotal = 0;
  Object.values(grouped.external).forEach(stats => {
    externalTotal += stats.total;
  });
  
  if (externalTotal !== categoryCounters.external) {
    console.warn(`Warning: External call counts differ. Stats total: ${externalTotal}, Tracked: ${categoryCounters.external}`);
  }

  // Verify category totals match the total calls count
  const categoryTotal = Object.values(categoryCounters).reduce((a, b) => a + b, 0);
  if (categoryTotal !== grouped.summary.total) {
    console.warn(`Warning: Category totals (${categoryTotal}) don't match total calls (${grouped.summary.total})`);
  }

  // Print summary information
  console.log('\n============= NETWORK CALL ANALYSIS =============\n');
  console.log('SUMMARY:');
  console.log(`  Total calls: ${grouped.summary.total}`);
  console.log(`  Successful: ${grouped.summary.successful}`);
  console.log(`  Failed: ${grouped.summary.failed}`);
  console.log('\nBY CATEGORY:');
  console.log(`  Core Bundles (Frontend Assets): ${grouped.summary.byCategory.coreBundles}`);
  console.log(`  Internal (Razorpay): ${grouped.summary.byCategory.internal}`);
  console.log(`  External: ${grouped.summary.byCategory.external}`);
  console.log(`  Unknown: ${grouped.summary.byCategory.unknown || 0}`);
  
  // Print external domains summary
  console.log('\nEXTERNAL DOMAINS:');
  Object.entries(grouped.external).forEach(([domain, stats]) => {
    console.log(`  ${domain}: ${stats.total} calls (${stats.successful} successful, ${stats.failed} failed)`);
  });

  return grouped;
} 