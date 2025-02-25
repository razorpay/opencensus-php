/**
 * Custom error class to handle client-specific API errors.
 * @extends Error
 */
export class ClientError<
  TMetadata extends Record<string, unknown> = Record<string, unknown>,
> extends Error {
  response: ResponseWithErrors;
  errorType: ErrorType;
  metadata: TMetadata;

  /**
   * Creates a new ClientError instance.
   * @param response - The response object from the API.
   * @param metadata - Additional metadata about the error.
   */
  constructor(response: ResponseWithErrors, metadata: TMetadata = {} as TMetadata) {
    const message = ClientError.extractMessage(response);
    super(message);
    this.response = response;
    this.errorType = ClientError.identifyErrorType(response);
    this.metadata = metadata;

    // Ensure stack trace is captured for this error
    if (typeof Error.captureStackTrace === 'function') {
      Error.captureStackTrace(this, ClientError);
    } else {
      this.stack = new Error().stack; // Fallback for older environments
    }
  }

  /**
   * Extracts error message from the response object.
   * @param response - The response object.
   * @returns {string} - The extracted error message.
   */
  private static extractMessage(response: ResponseWithErrors): string {
    try {
      if (response.errors && response.errors.length > 0) {
        return response.errors[0].message || `Error (Code: ${response.status})`;
      }

      // Attempt to parse JSON response body
      return response.json().then((responseBody) => {
        if (responseBody && typeof responseBody.message === 'string') {
          return responseBody.message;
        }
        return `Error (Code: ${response.status})`;
      });
    } catch {
      // Fallback if JSON parsing fails or no errors are present
      return `Error (Code: ${response.status})`;
    }
  }

  /**
   * Identifies the type of error based on the HTTP status code or response details.
   * @param response - The response object.
   * @returns {ErrorType} - The error type.
   */
  private static identifyErrorType(response: Response): ErrorType {
    if (response.status === 401) {
      return 'AuthenticationError';
    } else if (response.status === 403) {
      return 'AuthorizationError';
    } else if (response.status === 429) {
      return 'RateLimitError';
    } else if (response.status >= 400 && response.status < 500) {
      return 'ValidationError';
    } else if (response.status >= 500) {
      return 'ServerError';
    } else if (!response.ok) {
      return 'NetworkError';
    }
    return 'UnknownError';
  }

  /**
   * Determines if the error is an authentication error.
   * @returns {boolean}
   */
  public isAuthenticationError(): boolean {
    return this.errorType === 'AuthenticationError';
  }

  /**
   * Determines if the error is a rate-limit error.
   * @returns {boolean}
   */
  public isRateLimitError(): boolean {
    return this.errorType === 'RateLimitError';
  }

  /**
   * Determines if the error is a validation error.
   * @returns {boolean}
   */
  public isValidationError(): boolean {
    return this.errorType === 'ValidationError';
  }

  /**
   * Determines if the error is retryable.
   * @returns {boolean} - True if the error can be retried, false otherwise.
   */
  public isRetryable(): boolean {
    return ['RateLimitError', 'NetworkError', 'ServerError'].includes(this.errorType);
  }

  /**
   * Logs detailed information about the error for debugging purposes.
   */
  public logError(): void {
    console.error('Error Details:');
    console.error(`Type: ${this.errorType}`);
    console.error(`Status: ${this.response.status}`);
    console.error(`Message: ${this.message}`);
    console.error(`Metadata: ${JSON.stringify(this.metadata)}`);
    console.error(`Stack: ${this.stack}`);
  }

  /**
   * Extracts custom metadata fields from the response, if available.
   * @param field - The metadata field to extract.
   * @returns {TMetadata[K]} - The value of the metadata field.
   */
  public getMetadataField<K extends keyof TMetadata>(field: K): TMetadata[K] | undefined {
    return this.metadata[field];
  }

  /**
   * Creates a ClientError instance for a specific context.
   * @param response - The response object.
   * @param context - Additional context information.
   * @returns {ClientError<TMetadata>}
   */
  public static fromContext<TContext extends Record<string, unknown>>(
    response: ResponseWithErrors,
    context: TContext,
  ): ClientError<TContext> {
    return new ClientError(response, context);
  }
}

/**
 * Types of errors supported by the ClientError class.
 */
export type ErrorType =
  | 'AuthenticationError'
  | 'AuthorizationError'
  | 'RateLimitError'
  | 'ValidationError'
  | 'ServerError'
  | 'NetworkError'
  | 'UnknownError';

/**
 * A type representing a response object that may include errors.
 */
export interface ResponseWithErrors extends Response {
  errors?: Array<{ message: string; code?: string; [key: string]: unknown }>;
}
