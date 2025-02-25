import '@testing-library/jest-dom';
import 'whatwg-fetch';
import { TextEncoder, TextDecoder } from 'util';
import { nodeMswServer } from './msw.node';
import { QueryCache } from '@tanstack/react-query';

process.env.hostName = 'http://localhost:6006';
const queryCache = new QueryCache();
const TransformStream = require('web-streams-polyfill').TransformStream;

Object.assign(global, { TextDecoder, TextEncoder, __STAGE__: 'production' });
global.ResizeObserver = jest.fn().mockImplementation(() => ({
  observe: jest.fn(),
  unobserve: jest.fn(),
  disconnect: jest.fn(),
}));
global.TransformStream = jest.fn().mockImplementation(() => TransformStream);


beforeAll(() => nodeMswServer.listen({ onUnhandledRequest: 'error' }));
afterAll(() => nodeMswServer.close());
beforeEach(() => {
  const { getComputedStyle } = window;
  window.getComputedStyle = (elt) => getComputedStyle(elt);
  nodeMswServer.resetHandlers();

  window.rzpAnalytics = jest.fn();
  window.RZP = {};
});
afterEach(() => {
  queryCache.clear();
});

