const metricType = {
  RESPONSE_TIME: 'resources.responseTime',
  RESPONSE_CODE: 'resources.responseCode',
};

const getLocationUrl = url => {
  if (!url) {
    if (location.hash) {
      url = location.hash;
      url = url.replace('#', '');
    } else {
      url = location.pathname;
    }
    return url.split('/')[2];
  }

  return url.split('?')[0];
};

const captureResponseCode = response => {
  if (response.status) {
    window.rzpQMetrics.push({
      type: 'metrics',
      properties: {
        name: metricType.RESPONSE_CODE,
        labels: [
          {
            type: 'xmlhttprequest',
            url: getLocationUrl(response.request.responseURL),
            route: getLocationUrl(),
            code: String(response.status),
          },
        ],
      },
    });
  }
  return null;
};

export const captureXhrResponseMetrics = response => {
  if (window.rzpQMetrics) {
    captureResponseCode(response);
  }
};
