import { Request, Response, NextFunction } from 'express';
import Prometheus, { Counter, Histogram } from 'prom-client';
import { SHELL_SERVER_ROUTES } from '../../configs';
import { PROM_HISTOGRAM_API_TIME_IN_SEC_BUCKETS } from './utils';

/**
 * Parameter interface for creating a Prometheus histogram.
 */
interface PromClientParamT {
  metricName: string;
  metricDesc: string;
  possibleLabels?: string[];
  buckets?: number[];
}

/**
 * A dedicated Prometheus registry.
 */
const promRegistry = new Prometheus.Registry();

/**
 * Creates a Prometheus histogram, prefixes its name with an application name, and registers it.
 *
 * @param param - Object containing metric name, description, labels, and buckets.
 * @returns A Prometheus Histogram.
 */
export const createHistogram = ({
  metricName,
  metricDesc,
  possibleLabels,
  buckets,
}: PromClientParamT): Histogram => {
  const histogram = new Prometheus.Histogram({
    name: prefixNameWithAppName(metricName),
    help: metricDesc,
    labelNames: possibleLabels,
    buckets,
  });
  promRegistry.registerMetric(histogram);
  return histogram;
};

/**
 * Creates a Prometheus counter, prefixes its name with an application name, and registers it.
 *
 * @param param - Object containing metric name, description, labels, and buckets.
 * @returns A Prometheus Counter.
 */
export const createCounter = ({
  metricName,
  metricDesc,
  possibleLabels,
}: PromClientParamT): Counter => {
  const counter = new Prometheus.Counter({
    name: prefixNameWithAppName(metricName),
    help: metricDesc,
    labelNames: possibleLabels,
  });
  promRegistry.registerMetric(counter);
  return counter;
};

/**
 * Prefixes a metric name with a consistent application identifier.
 *
 * @param metricName - The original metric name.
 * @returns The prefixed metric name.
 */
const prefixNameWithAppName = (metricName: string): string => {
  return `dashboard_shell_${metricName}`;
};

/**
 * Registers the default Prometheus metrics.
 */
export const setDefaultMetrics = (): void => {
  Prometheus.collectDefaultMetrics({ register: promRegistry });
};

/**
 * Express handler for serving Prometheus metrics.
 *
 * @param _req - Express Request.
 * @param res - Express Response.
 * @returns The metrics in text format.
 */
export const metricsHandler = async (_req: Request, res: Response): Promise<any> => {
  const metrics = await promRegistry.metrics();
  res.setHeader('Content-Type', promRegistry.contentType);
  return res.status(200).send(metrics);
};

/**
 * Middleware to measure incoming HTTP request durations.
 *
 * This middleware relies on Express’s own route matching (via `req.route.path`)
 * so that the recorded route key exactly matches what Express has defined.
 * It then looks up that route pattern in `SHELL_SERVER_ROUTES` to obtain a key.
 *
 * **Important:** For `req.route` to be available, this middleware must be mounted
 * on a router (or after your route declarations) and not globally.
 *
 * @returns Express middleware function.
 */
export const measureRequestDurationsMiddleware = () => {
  // Histogram for measuring request durations.
  const httpRequestDurationHistogram = createHistogram({
    metricName: 'http_request_duration_seconds',
    metricDesc: 'HTTP request duration in seconds',
    possibleLabels: ['method', 'route_key', 'status'],
    buckets: PROM_HISTOGRAM_API_TIME_IN_SEC_BUCKETS,
  });

  // Counter for tracking total HTTP requests.
  const httpRequestCounter = createCounter({
    metricName: 'http_requests_total',
    metricDesc: 'Total number of HTTP requests received',
    possibleLabels: ['method', 'route_key', 'status'],
  });

  function getMatchingRouteKey(req: Request): string | null {
    if (req.route?.path) {
      const routePattern = req.route.path;

      switch (true) {
        case Array.isArray(routePattern) &&
          (routePattern.includes(SHELL_SERVER_ROUTES.FRONTEND_APPS) ||
            routePattern.includes(SHELL_SERVER_ROUTES.STRICT_FRONTEND_APPS)):
          return 'FRONTEND_ROUTES';
        default:
          return null;
      }
    }
    return null;
  }

  return (req: Request, res: Response, next: NextFunction) => {
    const start = process.hrtime();

    res.on('finish', () => {
      const diff = process.hrtime(start);
      const durationSeconds = diff[0] + diff[1] / 1e9;

      const matchedKey = getMatchingRouteKey(req);
      if (matchedKey) {
        // Record request duration
        httpRequestDurationHistogram
          .labels(req.method, matchedKey, `${res.statusCode}`)
          .observe(durationSeconds);

        // Increment the request counter for RPS metrics
        httpRequestCounter.labels(req.method, matchedKey, `${res.statusCode}`).inc();
      }
    });

    next();
  };
};
