import { Request } from 'express';
import { ADMIN_DASHBOARD_INTERNAL_URL, PHP_BASE_URL, STAGE } from '@apps/shell/src/env';

/**
 * Supported admin dashboard domains across all regions
 */
const ADMIN_DASHBOARD_DOMAINS = [
  'admin-dashboard.razorpay.com',
  'sg-admin-dashboard.razorpay.com',
  'us-admin-dashboard.razorpay.com',
] as const;

type AdminDashboardDomain = (typeof ADMIN_DASHBOARD_DOMAINS)[number];

/**
 * Returns the base URL for PHP services based on the request hostname
 * @param req - Express request object
 * @returns Base URL string for PHP services
 * @throws Error if request object is invalid
 */
export const getPhpBaseUrl = (req: Request): string | undefined => {
  const hostname = req.hostname;
  const normalizedHostname = hostname.toLowerCase();

  const isDev = STAGE === 'development';

  // Check if hostname is a known admin dashboard domain
  if (ADMIN_DASHBOARD_DOMAINS.includes(normalizedHostname as AdminDashboardDomain)) {
    return ADMIN_DASHBOARD_INTERNAL_URL;
  }

  // Default case: use PHP_BASE_URL for development, otherwise use hostname
  return isDev ? PHP_BASE_URL : `https://${normalizedHostname}`;
};
