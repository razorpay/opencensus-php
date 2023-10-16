// Polyfill "window.fetch" used in the React component.
import '@testing-library/jest-dom/extend-expect';
import { queryCache } from 'common/components/Bootstrap/Wrapper';
import 'jest-canvas-mock';
import 'regenerator-runtime/runtime';
import 'whatwg-fetch';
import { server } from '../../../../mocks/node';
process.env.hostName = 'http://localhost:6006';

const RetryTimes = process.env.UT_RETRY_TIMES || 3;

// Global mocks

global.ResizeObserver = jest.fn().mockImplementation(() => ({
  observe: jest.fn(),
  unobserve: jest.fn(),
  disconnect: jest.fn(),
}));

jest.mock('merchant/utils/ajax');
jest.mock('merchant/views/TicketSupport/utils.js', () => ({
  CreateTicketEmitter: jest.fn(),
}));
jest.mock('@razorpay/commander-services/analytics', () => {
  return {
    __esModule: true,
    default: {
      track: jest.fn(),
    },
  };
});
jest.mock('common/utils/analytics', () => ({
  ...jest.requireActual('common/utils/analytics'),
  analyticsTrack: jest.fn(),
  analyticsTrackWithUserInfo: jest.fn(),
}));
jest.mock('common/services/tracking/segment', () => ({
  ...jest.requireActual('common/services/tracking/segment'),
  analyticsTrack: jest.fn(),
}));
jest.mock('merchant/views/Transactions/v1/AnalyticsTrack', () => ({
  ...jest.requireActual('merchant/views/Transactions/v1/AnalyticsTrack'),
  selfServerTrack: jest.fn(),
  selfServeTrackResult: jest.fn(),
}));

// jest.mock('merchant/views/Transactions/v2/common/utils', () => ({
//   ...jest.requireActual('merchant/views/Transactions/v2/common/utils'),
//   isTransactionsV2Enabled: (_) => true,
// }));

jest.mock('common/i18', () => {
  return {
    __esModule: true,
    withI18Service: (Component) => (props) =>
      <Component {...props} i18={{ isConfigTagEnabled: jest.fn() }} />,
    useI18Service: () => ({
      isConfigTagEnabled: jest.fn(),
    }),
  };
});

beforeAll(() => server.listen({ onUnhandledRequest: 'error' }));
afterAll(() => server.close());

beforeEach(() => {
  const { getComputedStyle } = window;
  window.getComputedStyle = (elt) => getComputedStyle(elt);
  server.resetHandlers();

  window.rzpAnalytics = jest.fn();
  window.RZP = {};
});

if (!process.env.LISTENING_TO_UNHANDLED_REJECTION) {
  process.on('unhandledRejection', (reason) => {
    throw reason;
  });

  // Avoid memory leak by adding too many listeners
  process.env.LISTENING_TO_UNHANDLED_REJECTION = true;
}

if (!process.env.LISTENING_TO_UNHANDLED_REJECTION) {
  process.on('unhandledRejection', (reason) => {
    throw reason;
  });

  // Avoid memory leak by adding too many listeners
  process.env.LISTENING_TO_UNHANDLED_REJECTION = true;
}

afterEach(() => {
  queryCache.clear();
});

if (process.env.CI === 'true') {
  jest.retryTimes(RetryTimes, {
    logErrorsBeforeRetry: true,
  });
}
