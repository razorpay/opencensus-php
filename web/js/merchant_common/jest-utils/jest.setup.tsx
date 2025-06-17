import 'whatwg-fetch';
import '@testing-library/jest-dom';
import { QueryCache } from '@tanstack/react-query';
import 'jest-canvas-mock';
import 'regenerator-runtime/runtime';
import { nodeMswServer } from './msw.node';

process.env.hostName = 'http://localhost:6006';

const queryCache = new QueryCache();

const RetryTimes = process.env.UT_RETRY_TIMES || 3;

const TransformStream = require('web-streams-polyfill').TransformStream;

global.ResizeObserver = jest.fn().mockImplementation(() => ({
  observe: jest.fn(),
  unobserve: jest.fn(),
  disconnect: jest.fn(),
}));
global.TransformStream = jest.fn().mockImplementation(() => TransformStream);
Object.assign(global, { __STAGE__: 'production' });

jest.mock('merchant/utils/ajax');
jest.mock('merchant/views/TicketSupport/utils.js', () => ({
  CreateTicketEmitter: jest.fn(),
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

jest.mock('common/i18', () => {
  return {
    __esModule: true,
    withI18Service: (Component) => (props) =>
      <Component {...props} i18={{ isConfigTagEnabled: jest.fn() }} />,
    useI18Service: () => ({
      isConfigTagEnabled: jest.fn(),
    }),
    withI18nifyState: (Component) => (props) => <Component {...props} setI18nState={jest.fn()} />,
  };
});

beforeAll(() => nodeMswServer.listen({ onUnhandledRequest: 'error' }));
afterAll(() => nodeMswServer.close());

beforeEach(() => {
  const { getComputedStyle } = window;
  window.getComputedStyle = (elt) => getComputedStyle(elt);
  nodeMswServer.resetHandlers();

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

afterEach(() => {
  queryCache.clear();
});

if (process.env.CI === 'true') {
  jest.retryTimes(RetryTimes, {
    logErrorsBeforeRetry: true,
  });
}
