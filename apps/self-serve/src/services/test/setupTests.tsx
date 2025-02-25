// Polyfill "window.fetch" used in the React component.

import '@testing-library/jest-dom/extend-expect';
import 'whatwg-fetch';
import 'regenerator-runtime/runtime';
import { type ComponentType } from 'react';
import { clearStore } from '@federated/apps/shell/commonStore';
import { server } from '../mocks/setup';
import { TextEncoder, TextDecoder } from 'util';

Object.assign(global, { TextDecoder, TextEncoder, __STAGE__: 'production' });

process.env.hostName = 'http://localhost:6006';

const RetryTimes = 3;

// Global mocks

global.ResizeObserver = jest.fn().mockImplementation(() => ({
  observe: jest.fn(),
  unobserve: jest.fn(),
  disconnect: jest.fn(),
}));

jest.mock('react-lottie', () => ({
  default: 'div',
}));

jest.mock('@dashboards/payments/reducers/session', () => {
  return {
    initialState: {
      user: {
        merchants: {},
        isNeostoneFlowEnabled: () => false,
        isICICILinkedCAFlowEnabled: () => false,
        merchant: { currency: 'INR', country_code: 'IN' },
        tags: [],
        configTags: {},
      },
      org: {
        security_branding_logo: 'https://cdn.razorpay.com/static/assets/pay_methods_branding.png',
      },
      mode: 'test',
      partnerMode: 'test',
      modeFormatted: 'Test',
      partnerModeFormatted: 'Test',
      highlightMode: true,
      isTourVisible: false,
      isUsingPartnerMode: false,
      user_segment_data: null,
      isTagsLoaded: false,
      isHelpWidgetVisible: true,
    },
  };
});

jest.mock('@libs/shared-utils', () => ({
  ...jest.requireActual('@libs/shared-utils'),
  toTitleCase: jest.fn(jest.requireActual('@libs/shared-utils').toTitleCase),
  useMobile: jest.fn(),
  analyticsTrack: jest.fn(),
  analyticsTrackWithUserInfo: jest.fn(),
  getCommonAnalyticsProperties: jest.fn(),
  decodeSensitiveFields: jest.fn((search) => search),
}));

jest.mock('common/i18', () => {
  return {
    __esModule: true,
    withI18Service:
      (Component: ComponentType<any>) =>
      (props: any): JSX.Element =>
        <Component {...props} i18={{ isConfigTagEnabled: jest.fn() }} />,
    useI18Service: (): { isConfigTagEnabled: jest.MockedFunction<() => boolean> } => ({
      isConfigTagEnabled: jest.fn(),
    }),
    withI18nifyState: (Component) => (props) => <Component {...props} setI18nState={jest.fn()} />,
  };
});

beforeAll(() => server.listen({ onUnhandledRequest: 'error' }));
afterAll(() => server.close());

beforeEach(() => {
  const { getComputedStyle } = window;
  window.getComputedStyle = (elt) => getComputedStyle(elt);
  server.resetHandlers();
  clearStore();

  window.rzpAnalytics = jest.fn();
  window.RZP = {};
});

if (!process.env.LISTENING_TO_UNHANDLED_REJECTION) {
  process.on('unhandledRejection', (reason) => {
    throw reason;
  });

  // Avoid memory leak by adding too many listeners
  process.env.LISTENING_TO_UNHANDLED_REJECTION = 'true';
}

if (!process.env.LISTENING_TO_UNHANDLED_REJECTION) {
  process.on('unhandledRejection', (reason) => {
    throw reason;
  });

  // Avoid memory leak by adding too many listeners
  process.env.LISTENING_TO_UNHANDLED_REJECTION = 'true';
}

if (process.env.CI === 'true') {
  jest.retryTimes(RetryTimes, {
    logErrorsBeforeRetry: true,
  });
}
