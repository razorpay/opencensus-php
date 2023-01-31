// Polyfill "window.fetch" used in the React component.
import 'whatwg-fetch';

import '@testing-library/jest-dom/extend-expect';
import 'regenerator-runtime/runtime';
import { queryCache } from 'common/components/Bootstrap/Wrapper';
import { server } from '../../../../mocks/node';
process.env.hostName = 'http://localhost:6006';

// Global mocks
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
jest.mock('merchant/views/Transactions/AnalyticsTrack', () => ({
  ...jest.requireActual('merchant/views/Transactions/AnalyticsTrack'),
  selfServerTrack: jest.fn(),
  selfServeTrackResult: jest.fn(),
}));
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
