import getConnectionType from '../utils/getConnectionType';
import trackEvents from './analytics';

const METRICS = {
  API_LATENCY_ON_FE: 'fe_api_latency',
};

// Beta Grafana dashboard to check this
// https://grafana.np.razorpay.in/d/VB8rmKbnk/api-latency-platform-acquisition-fe-beta?viewPanel=2&orgId=1&from=now-90d&to=now
// Concierge: https://concierge.stage.razorpay.in/resources/aws/sg-01a869349823a07a3/common-concierge
export const trackApiLatency = ({ route, apiResponseTime }) => {
  trackEvents.prometheus({
    properties: {
      metric: METRICS.API_LATENCY_ON_FE,
      route,
      connectionType: getConnectionType(),
      time: apiResponseTime,
    },
    isPageMetrics: true,
  });
};
