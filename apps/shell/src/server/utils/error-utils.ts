export const getErrorMessage = (error: unknown): string | undefined => {
  if (!error) return;
  if (error instanceof Error) return error.message;
  return String(error);
};

export const getErrorStack = (error: unknown): string | undefined => {
  if (!error) return;
  if (error instanceof Error) return error.stack || '';
  return '';
};

export enum SHELL_ERROR_TRACE {
  UNAUTHENTICATED = 'Unauthenticated',
  INTERNAL_SERVER_ERROR = 'Internal Server Error',
  PAGE_NOT_FOUND = 'Page Not Found',
  BAD_REQUEST = 'Bad Request',
  FORBIDDEN = 'Forbidden',
  METHOD_NOT_ALLOWED = 'Method Not Allowed',
  REQUEST_TIMEOUT = 'Request Timeout',
  TOO_MANY_REQUESTS = 'Too Many Requests',
  BAD_GATEWAY = 'Bad Gateway',
  SERVICE_UNAVAILABLE = 'Service Unavailable',
  GATEWAY_TIMEOUT = 'Gateway Timeout',
}

// Interface for individual API failure information
export interface ApiFailureInfo {
  middlewareName: string;
  statusCode: number;
  message: string;
  dashboardBackendRequestId?: string;
  apiPath?: string;
  timestamp: string;
  moduleName?: string;
  error?: any;
}

export interface ShellErrorConstructorArgs {
  message: string;
  statusCode?: number;
  moduleName?: string;
  trace?: SHELL_ERROR_TRACE;
  context?: any;
  originalError?: Error;
  // New property for multiple API failures
  apiFailures?: ApiFailureInfo[];
}

export class ShellError extends Error {
  public statusCode: number;
  public moduleName: string;
  public trace?: SHELL_ERROR_TRACE;
  public context?: any;
  public originalError?: Error;
  // New property to store multiple API failures
  public apiFailures?: ApiFailureInfo[];

  constructor({
    message,
    statusCode = 500,
    moduleName = 'UNKNOWN',
    trace,
    context,
    originalError,
    apiFailures,
  }: ShellErrorConstructorArgs) {
    super(message);
    this.name = 'ShellError';
    this.statusCode = statusCode;
    this.moduleName = moduleName;
    this.trace = trace;
    this.context = context;
    this.originalError = originalError;
    this.apiFailures = apiFailures;

    // Maintain proper stack trace for V8
    if (Error.captureStackTrace) {
      Error.captureStackTrace(this, ShellError);
    }
  }
}
