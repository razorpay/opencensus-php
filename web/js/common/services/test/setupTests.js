// Polyfill "window.fetch" used in the React component.
import '@testing-library/jest-dom/extend-expect';
import { QueryCache } from '@tanstack/react-query';

import 'jest-canvas-mock';
import 'regenerator-runtime/runtime';
import 'whatwg-fetch';

import { server } from '../../../../mocks/node';

process.env.hostName = 'http://localhost:6006';

const queryCache = new QueryCache();

const RetryTimes = process.env.UT_RETRY_TIMES || 3;

const TransformStream = require('web-streams-polyfill').TransformStream;

Object.assign(global, { __STAGE__: 'production' });

global.ResizeObserver = jest.fn().mockImplementation(() => ({
  observe: jest.fn(),
  unobserve: jest.fn(),
  disconnect: jest.fn(),
}));
global.TransformStream = jest.fn().mockImplementation(() => TransformStream);

jest.mock('merchant/utils/ajax');
jest.mock('merchant/views/TicketSupport/utils.js', () => ({
  CreateTicketEmitter: jest.fn(),
}));

jest.mock(
  'merchant/views/PartnerDashboard/ClientAccounts/ClientDetails/ClientPOSOrderList/constants',
  () => {
    const mockProduct = jest.requireActual(
      'merchant/views/PartnerDashboard/ClientAccounts/ClientDetails/ClientPOSOrderList/__tests__/mocks/fixtures',
    ).MOCK_PRODUCT;

    const mockProductNew = {
      ...mockProduct,
      name: 'mock-product-new',
      code: 'mock-product-new',
      productTitle: 'Mock Product New',
    };

    return {
      ...jest.requireActual(
        'merchant/views/PartnerDashboard/ClientAccounts/ClientDetails/ClientPOSOrderList/constants',
      ),
      PRODUCT_DESCRIPTIONS: {
        'mock-product': mockProduct,
        'mock-product-new': mockProductNew,
      },
    };
  },
);

jest.mock('@libs/shared-utils', () => ({
  ...jest.requireActual('@libs/shared-utils'),
  analyticsTrack: jest.fn(),
  analyticsTrackWithUserInfo: jest.fn(),
}));

jest.mock('common/utils/localStorage', () => {
  return {
    __esModule: true,
    ...jest.requireActual('common/utils/localStorage'),
  };
});

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

beforeAll(() => {
  server.listen({ onUnhandledRequest: 'error' });
  Object.defineProperty(window.Element.prototype, 'scroll', {
    writable: true,
    value: jest.fn(),
  });

  Object.defineProperty(window.Element.prototype, 'scrollLeft', {
    writable: true,
    value: 1,
  });
});

afterAll(() => {
  server.close();
  Object.defineProperty(window.Element.prototype, 'scroll', {
    writable: true,
    value: undefined,
  });

  Object.defineProperty(window.Element.prototype, 'scrollLeft', {
    writable: true,
    value: 0,
  });
});

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
  const actualLocalStorage = window?.localStorage;

  Object.defineProperty(global, 'localStorage', {
    value: {
      getItem: jest.fn((...args) => actualLocalStorage?.getItem?.(...args)),
      setItem: jest.fn((...args) => actualLocalStorage?.setItem?.(...args)),
      removeItem: jest.fn((...args) => actualLocalStorage?.removeItem?.(...args)),
      clear: jest.fn(() => actualLocalStorage?.clear?.()),
    },
    writable: true,
  });
});

if (process.env.CI === 'true') {
  jest.retryTimes(RetryTimes, {
    logErrorsBeforeRetry: true,
  });
}
