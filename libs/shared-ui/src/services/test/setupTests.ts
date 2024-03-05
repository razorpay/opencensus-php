// Polyfill "window.fetch" used in the React component.
import '@testing-library/jest-dom/extend-expect';
import 'regenerator-runtime/runtime';

process.env.hostName = 'http://localhost:6006';

const RetryTimes = 3;

declare global {
  interface Window {
    rzpAnalytics: jest.Mock;
    RZP: any;
  }
}

// Global mocks

global.ResizeObserver = jest.fn().mockImplementation(() => ({
  observe: jest.fn(),
  unobserve: jest.fn(),
  disconnect: jest.fn(),
}));

beforeEach(() => {
  const { getComputedStyle } = window;
  window.getComputedStyle = (elt) => getComputedStyle(elt);
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
