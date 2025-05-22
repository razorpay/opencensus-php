// Define whitelisted headers to keep
export const WHITELISTED_REQUEST_HEADERS = ['accept', 'referer', 'host'];

export const WHITELISTED_RESPONSE_HEADERS = [
  'x-amzn-trace-id',
  'x-http-status',
  'x-razorpay-request-id',
  'x-request-id',
  'content-type',
];

/**
 * Comprehensive list of asset MIME types that should be treated specially
 */
export const ASSET_MIME_TYPES = [
  // Images
  'image/',
  // Fonts
  'font/',
  // CSS
  'text/css',
  // JavaScript
  'text/javascript',
  'application/javascript',
  'application/x-javascript',
  // Other web assets
  'text/plain',
  'text/xml',
  'application/xml',
  'application/wasm',
  // Media files
  'audio/',
  'video/',
  // Document formats
  'application/pdf',
  // Archive formats
  'application/zip',
  'application/x-gzip',
  'application/x-tar',
  // Static web resources
  'application/manifest+json',
  'text/html',
  'application/xhtml+xml',
];
