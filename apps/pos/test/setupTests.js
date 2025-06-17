// Mock DOM methods used in components like Carousel
Element.prototype.scroll = jest.fn();
Element.prototype.scrollTo = jest.fn();
Element.prototype.scrollIntoView = jest.fn();

// Mock localStorage
const localStorageMock = (function () {
  let store = {};
  return {
    getItem: jest.fn((key) => store[key] || null),
    setItem: jest.fn((key, value) => {
      store[key] = value.toString();
    }),
    removeItem: jest.fn((key) => {
      delete store[key];
    }),
    clear: jest.fn(() => {
      store = {};
    }),
  };
})();
Object.defineProperty(window, 'localStorage', {
  value: localStorageMock,
});

// Mock Blade UI breakpoints - removed toast mocks to allow actual toast functionality

jest.mock('merchant/store', () => {
  return {
    __esModule: true,
    default: {
      getState: () => ({
        session: {
          user: {
            id: 'mock-user-id',
            business_operation_address: 'test operation address',
            business_operation_city: 'test operation city',
            business_operation_state: 'test operation state',
            business_operation_pin: '123456',
            business_registered_address: 'test registered address',
            business_registered_city: 'test registered city',
            business_registered_state: 'test registered state',
            business_registered_pin: '123456',
          },
          mode: 'test',
          org: 'test-org',
          isUsingPartnerMode: false,
          partnerMode: 'test-partner',
        },
      }),
    },
    getUser: () => ({
      id: 'mock-user-id',
      business_operation_address: 'test operation address',
      business_operation_city: 'test operation city',
      business_operation_state: 'test operation state',
      business_operation_pin: '123456',
      business_registered_address: 'test registered address',
      business_registered_city: 'test registered city',
      business_registered_state: 'test registered state',
      business_registered_pin: '123456',
    }),
    getMode: jest.fn(() => 'test'),
    getOrg: jest.fn(() => 'test-org'),
    getPartnerMode: jest.fn(() => 'test-partner'),
    storeWithInitialState: jest.fn(),
  };
});

// Mock canvas and lottie-web to prevent errors with animation
jest.mock('react-lottie', () => ({
  __esModule: true,
  default: () => <div data-testid="mock-lottie-animation" />,
}));

// Add jest-canvas-mock
require('jest-canvas-mock');

jest.mock('apps/pos/src/app/views/SelfServe/constants', () => {
  const mockProduct = jest.requireActual(
    'apps/pos/src/app/views/SelfServe/__tests__/mocks/fixtures',
  ).MOCK_PRODUCT;

  const mockProductNew = {
    ...mockProduct,
    name: 'mock-product-new',
    code: 'mock-product-new',
    productTitle: 'Mock Product New',
  };

  return {
    ...jest.requireActual('apps/pos/src/app/views/SelfServe/constants'),
    PRODUCT_DESCRIPTIONS: {
      'mock-product': mockProduct,
      'mock-product-new': mockProductNew,
    },
  };
});
