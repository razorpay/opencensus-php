import '@testing-library/jest-dom';
import 'whatwg-fetch';
import { TextEncoder, TextDecoder } from 'util';
import { QueryCache } from '@tanstack/react-query';
import { nodeMswServer } from './msw.node';
import { useStore } from './__mocks__/commonStore';

const mockUseStore = useStore;

process.env['hostName'] = 'http://localhost:6006';
const queryCache = new QueryCache();
const TransformStream = require('web-streams-polyfill').TransformStream;

Object.assign(global, { TextDecoder, TextEncoder, __STAGE__: 'production' });
global.ResizeObserver = jest.fn().mockImplementation(() => ({
  observe: jest.fn(),
  unobserve: jest.fn(),
  disconnect: jest.fn(),
}));
global.TransformStream = jest.fn().mockImplementation(() => TransformStream);

// Mock federated modules
jest.mock(
  '@federated/apps/shell/commonStore',
  () => ({
    useStore: mockUseStore,
  }),
  { virtual: true },
);

// Mock the GraphQL module
jest.mock(
  '@federated/apps/shell/graphql',
  () => ({
    gql: (strings: TemplateStringsArray, ...values: any[]) => strings.join(''),
    useQuery: jest.fn().mockReturnValue({ data: null, isLoading: false, error: null }),
    useMutation: jest.fn().mockReturnValue([jest.fn(), { isLoading: false, error: null }]),
    graphqlRequest: jest.fn().mockResolvedValue({}),
    graphqlClient: {
      setHeader: jest.fn(),
    },
    graphqlRequestQuery: jest.fn().mockResolvedValue({}),
    graphqlRequestMutation: jest.fn().mockResolvedValue({}),
  }),
  { virtual: true },
);

// Extend Window interface to add custom properties
declare global {
  interface Window {
    rzpAnalytics: jest.Mock;
    RZP?: {
      appName: string;
      appHost: string;
    };
  }
}

beforeAll(() => nodeMswServer.listen({ onUnhandledRequest: 'error' }));
afterAll(() => nodeMswServer.close());
beforeEach(() => {
  const { getComputedStyle } = window;
  window.getComputedStyle = (elt) => getComputedStyle(elt);
  nodeMswServer.resetHandlers();

  window.rzpAnalytics = jest.fn();
  window.RZP = {
    appName: 'test-app',
    appHost: 'http://localhost',
  };
});
afterEach(() => {
  queryCache.clear();
});
