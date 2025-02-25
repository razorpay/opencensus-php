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

export class ShellError extends Error {
  public trace: SHELL_ERROR_TRACE;
  public moduleName: string;
  public statusCode: number;
  public message: string;
  public context?: unknown;

  constructor({
    trace,
    moduleName,
    statusCode,
    message,
    context,
  }: {
    moduleName: string;
    trace?: SHELL_ERROR_TRACE;
    statusCode?: number;
    message?: string;
    context?: unknown;
  }) {
    super();
    this.name = 'ShellError';
    this.moduleName = moduleName;
    this.trace = trace || SHELL_ERROR_TRACE.INTERNAL_SERVER_ERROR;
    this.statusCode = statusCode || 500;
    this.message = message || '';
    this.context = context;
    Object.setPrototypeOf(this, new.target.prototype);
  }
}
