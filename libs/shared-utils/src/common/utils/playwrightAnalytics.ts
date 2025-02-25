import { getItemFromLocalStorage, analyticsTrack, getCommonAnalyticsProperties } from './';

// Define types for the configuration keys stored in localStorage
interface ConfigKeys {
  regressionEnv?: string;
  itfLabel?: string;
  baseUrl?: string;
  testName?: string;
}

// Define the type for the API response or error handling
interface ResponseProperties {
  success: boolean;
  apiStatusCode: string | number;
  statusCodeCategory: string;
  message?: string;
}

// Define the type for the parameters used in analytics tracking
interface AnalyticsParams {
  params: {
    url: string;
    method?: string;
  };
  response?: {
    data?: {
      success?: boolean;
      status_code?: number;
    };
  };
  error?: {
    response?: {
      status?: number;
    };
    message?: string;
  };
}

// Example config keys used
const configKeys: Array<keyof ConfigKeys> = ['regressionEnv', 'itfLabel', 'baseUrl', 'testName'];

/**
 * Determines the category of the status code.
 * 
 * @param statusCode - The HTTP status code.
 * @returns The category of the status code (e.g., '2xx', '4xx').
 * 
 * @example
 * getStatusCodeCategory(404); // '4xx'
 * getStatusCodeCategory(200); // '2xx'
 */
const getStatusCodeCategory = (statusCode: number): string => {
  const categories = [
    { range: [200, 300], category: '2xx' },
    { range: [300, 400], category: '3xx' },
    { range: [400, 500], category: '4xx' },
    { range: [500, 600], category: '5xx' },
  ];

  for (const { range, category } of categories) {
    if (statusCode >= range[0] && statusCode < range[1]) {
      return category;
    }
  }

  return 'unknown';
};

/**
 * Extracts response properties from either the response or error object.
 * 
 * @param response - The API response object.
 * @param error - The error object, if applicable.
 * @returns An object containing the success state, status code, category, and error message (if any).
 * 
 * @example
 * getResponseProperties({ response: { data: { success: true, status_code: 200 } } });
 * // { success: true, apiStatusCode: 200, statusCodeCategory: '2xx' }
 * 
 * getResponseProperties({ error: { response: { status: 404 }, message: 'Not Found' } });
 * // { success: false, apiStatusCode: 404, statusCodeCategory: '4xx', message: 'Not Found' }
 */
const getResponseProperties = ({
  response,
  error,
}: {
  response?: AnalyticsParams['response'];
  error?: AnalyticsParams['error'];
}): ResponseProperties => {
  if (response) {
    const { data: { success = false, status_code = 'unknown' } = {} } = response;
    return {
      success,
      apiStatusCode: status_code,
      statusCodeCategory: getStatusCodeCategory(status_code as unknown as number),
    };
  } else {
    const { response: { status = 'unknown' } = {}, message = '' } = error || {};
    return {
      success: false,
      apiStatusCode: status,
      statusCodeCategory: getStatusCodeCategory(status as number),
      message,
    };
  }
};

/**
 * Captures Playwright analytics and sends relevant data to the analytics tracking service.
 * 
 * @param params - The parameters required for Playwright analytics capture, including API response and error.
 * 
 * @example
 * capturePlaywrightAnalytics({
 *   params: { url: 'https://api.example.com', method: 'POST' },
 *   response: { data: { success: true, status_code: 200 } },
 * });
 */
export const capturePlaywrightAnalytics = ({ params, response, error }: AnalyticsParams): void => {
  try {
    // Fetch the relevant config values from local storage
    const configValues = configKeys.reduce<ConfigKeys>((acc, key) => {
      const value = getItemFromLocalStorage(key);
      if (value) {
        acc[key] = value;
      }
      return acc;
    }, {});

    const { regressionEnv, itfLabel, baseUrl, testName } = configValues;

    if (regressionEnv === 'playwright') {
      const { url: serviceUrl, method = 'GET' } = params;
      const { apiStatusCode, statusCodeCategory, success, message = '' } = getResponseProperties({
        response,
        error,
      });
      const { pathname } = window.location;

      // Define analytics properties
      const properties = {
        itfLabel,
        baseUrl,
        serviceUrl,
        method,
        apiStatusCode,
        statusCodeCategory,
        success,
        message,
        testName,
      };

      // Capture the analytics event
      analyticsTrack({
        objectName: 'Playwright E2E Error Metrics',
        actionName: 'captured',
        screen: pathname,
        properties: {
          ...getCommonAnalyticsProperties(window.rzp_user),
          ...properties,
        },
      });
    }
  } catch (err) {
    // @ts-ignore
    if (process.env.PUBLIC_ENV === 'development') {
      console.error('Error capturing Playwright analytics', err);
    }
  }
};
