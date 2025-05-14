import fetch, {
  type RequestInit,
  type Response,
  type Headers,
  type HeadersInit,
  type AbortError,
  type BodyInit,
  type Request,
} from 'node-fetch';
import { URL } from 'url';
import { SHELL_EXTERNAL_API_ROUTES } from '../../configs';
import prom from '@apps/shell/src/server/services/promMetrics';
import { PROM_HISTOGRAM_API_TIME_IN_SEC_BUCKETS } from '../promMetrics/utils';
import { INSTANCE_TYPE } from '@apps/shell/src/env';

/**
 * Reverses the SHELL_EXTERNAL_API_ROUTES for quick lookup
 * 
 */
const REVERSED_API_ROUTE_MAP: Record<string, keyof typeof SHELL_EXTERNAL_API_ROUTES> =
  Object.entries(SHELL_EXTERNAL_API_ROUTES).reduce((acc, [key, path]) => {
    acc[path] = key as keyof typeof SHELL_EXTERNAL_API_ROUTES;
    return acc;
  }, {} as Record<string, keyof typeof SHELL_EXTERNAL_API_ROUTES>);

/**
 * Metric labels for Prometheus.
 */
const metricLabels = ['method', 'api_route', 'status', 'deployment_type'];

/**
 * Prometheus metric: [TTFB] Time to receive response headers.
 */
const shellFetchResponseTimeHistogram = prom.createHistogram({
  metricName: 'api_response_time_seconds',
  metricDesc: 'Time to receive response headers from external API calls',
  possibleLabels: metricLabels,
  buckets: PROM_HISTOGRAM_API_TIME_IN_SEC_BUCKETS,
});

/**
 * Prometheus metric: Total time to consume response body.
 */
const shellFetchTotalTimeHistogram = prom.createHistogram({
  metricName: 'api_total_time_seconds',
  metricDesc: 'Total time to consume response body from external API calls',
  possibleLabels: metricLabels,
  buckets: PROM_HISTOGRAM_API_TIME_IN_SEC_BUCKETS,
});

/**
 * Prometheus metric: Total external API calls count.
 */
const shellFetchCallCounter = prom.createCounter({
  metricName: 'api_call_total',
  metricDesc: 'Total external API calls count',
  possibleLabels: metricLabels,
});

/**
 * Prometheus metric: Total external API call errors.
 */
const shellFetchErrorCounter = prom.createCounter({
  metricName: 'api_error_total',
  metricDesc: 'Total external API call errors',
  possibleLabels: [...metricLabels.slice(0, 2), 'error_type', 'deployment_type'],
});

/**
 * Prometheus metric: Request body sizes for external API calls.
 */
const shellFetchRequestSizeHistogram = prom.createHistogram({
  metricName: 'api_request_size_bytes',
  metricDesc: 'Request body sizes for external API calls',
  possibleLabels: [...metricLabels.slice(0, 2), 'deployment_type'],
  buckets: [100, 1024, 10240, 102400, 1048576],
});

/**
 * Context for metric tracking.
 */
interface MetricContext {
  method: string;
  apiRouteKey: string;
  status: string;
  startTime: [number, number];
}
/**
 * Tracks the body consumption metrics (total request time from start -> body read).
 * @param originalMethod - The original body method (text, json, etc.).
 * @param context - Context for Prometheus metrics (includes startTime).
 * @returns The result of the original method.
 */
async function trackBodyConsumption<T>(
  originalMethod: () => Promise<T>,
  context: MetricContext,
): Promise<T> {
  try {
    // Parse the body using the original method
    const result = await originalMethod();

    // Measure total time from context.startTime until body is fully consumed
    const totalDuration = process.hrtime(context.startTime);
    shellFetchTotalTimeHistogram
      .labels(context.method, context.apiRouteKey, context.status, INSTANCE_TYPE)
      .observe(totalDuration[0] + totalDuration[1] / 1e9);

    return result;
  } catch (error) {
    // On error, also measure total time from start
    const totalDuration = process.hrtime(context.startTime);
    const errorType =
      error instanceof SyntaxError
        ? 'body_parsing'
        : (error as any)?.type === 'aborted'
        ? 'aborted'
        : (error as any)?.name === 'FetchError'
        ? 'network'
        : 'unknown';

    shellFetchErrorCounter
      .labels(context.method, context.apiRouteKey, errorType, INSTANCE_TYPE)
      .inc();

    shellFetchTotalTimeHistogram
      .labels(context.method, context.apiRouteKey, context.status, INSTANCE_TYPE)
      .observe(totalDuration[0] + totalDuration[1] / 1e9);

    throw error;
  }
}

/**
 * `shellFetch`: Monitors external API calls using Prometheus metrics.
 * Only tracks calls that match `SHELL_EXTERNAL_API_ROUTES`.
 * @param url - The URL for the API call.
 * @param options - Optional fetch options (method, headers, body).
 * @returns The original fetch `Response` with monitoring.
 */
export async function shellFetch(url: string, options: RequestInit = {}): Promise<Response> {
  const method = (options.method || 'GET').toUpperCase();
  const parsedUrl = new URL(url);

  // Extract the path and query (excluding hostname)
  const pathWithQuery = parsedUrl.pathname + parsedUrl.search;

  // Check if this matches any predefined route
  const apiRouteKey = REVERSED_API_ROUTE_MAP[pathWithQuery];

  // Skip tracking if route doesn't match SHELL_EXTERNAL_API_ROUTES
  if (!apiRouteKey) {
    return fetch(url, options);
  }

  const startTime = process.hrtime();

  // Track request size (if body exists)
  if (options.body) {
    const size =
      typeof options.body === 'string'
        ? Buffer.byteLength(options.body)
        : options.body instanceof Buffer
        ? options.body.length
        : 0;
    shellFetchRequestSizeHistogram.labels(method, apiRouteKey, INSTANCE_TYPE).observe(size);
  }

  try {
    const response = await fetch(url, options);
    const status = response.status.toString();

    // Track response header timing
    const headerDuration = process.hrtime(startTime);
    shellFetchResponseTimeHistogram
      .labels(method, apiRouteKey, status, INSTANCE_TYPE)
      .observe(headerDuration[0] + headerDuration[1] / 1e9);

    shellFetchCallCounter.labels(method, apiRouteKey, status, INSTANCE_TYPE).inc();

    const metricContext: MetricContext = { method, apiRouteKey, status, startTime };

    // Capture the original methods before overriding them.
    const originalText = response.text.bind(response);
    const originalJson = response.json.bind(response);
    const originalArrayBuffer = response.arrayBuffer.bind(response);
    const originalBlob = response.blob.bind(response);
    // If using node-fetch's extended method:
    const originalBuffer = response.buffer?.bind(response);

    // Wrap response body consumption methods without causing recursion.
    return Object.assign(response, {
      text: () => trackBodyConsumption(originalText, metricContext),
      json: () => trackBodyConsumption(originalJson, metricContext),
      arrayBuffer: () => trackBodyConsumption(originalArrayBuffer, metricContext),
      blob: () => trackBodyConsumption(originalBlob, metricContext),
      // Only override if the originalBuffer exists.
      ...(originalBuffer && { buffer: () => trackBodyConsumption(originalBuffer, metricContext) }),
    });
  } catch (error) {
    const errorDuration = process.hrtime(startTime);
    const errorType =
      error instanceof SyntaxError
        ? 'body_parsing'
        : (error as any)?.type === 'aborted'
        ? 'aborted'
        : (error as any).name === 'FetchError'
        ? 'network'
        : 'unknown';

    shellFetchErrorCounter.labels(method, apiRouteKey, errorType, INSTANCE_TYPE).inc();

    shellFetchResponseTimeHistogram
      .labels(method, apiRouteKey, 'error', INSTANCE_TYPE)
      .observe(errorDuration[0] + errorDuration[1] / 1e9);

    throw error;
  }
}

export type { RequestInit, Response, Headers, HeadersInit, AbortError, BodyInit, Request };
