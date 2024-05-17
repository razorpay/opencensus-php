import { analyticsTrack } from 'common/utils/analytics';
import { getItem } from 'common/utils/localStorage';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

const configKeys = ['regressionEnv', 'itfLabel', 'baseUrl', 'testName'];

const getStatusCodeCategory = (statusCode) => {
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

const getResponseProperties = ({ response, error }) => {
  if (response) {
    const { data: { success, status_code } = {} } = response || {};
    return {
      success,
      apiStatusCode: status_code || 'unknown',
      statusCodeCategory: getStatusCodeCategory(status_code),
    };
  } else {
    const { response: { status } = {}, message } = error || {};
    return {
      success: false,
      apiStatusCode: status || 'unknown',
      statusCodeCategory: getStatusCodeCategory(status),
      message,
    };
  }
};

export const capturePlaywrightAnalytics = ({ params, response, error }) => {
  try {
    const { regressionEnv, itfLabel, baseUrl, testName } = configKeys.reduce((acc, key) => {
      const value = getItem(key);
      if (value) {
        acc[key] = value;
      }
      return acc;
    }, {});
    if (regressionEnv === 'playwright') {
      const { url: serviceUrl, method = 'GET' } = params;
      const {
        apiStatusCode,
        statusCodeCategory,
        success,
        message = '',
      } = getResponseProperties({
        response,
        error,
      });
      const { pathname } = window.location;
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
  } catch (error) {
    if (process.env.PUBLIC_ENV == 'development') {
      console.error('Error capturing Playwright analytics');
    }
  }
};
