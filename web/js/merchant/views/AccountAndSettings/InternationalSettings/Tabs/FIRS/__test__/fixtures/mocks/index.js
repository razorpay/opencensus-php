import useFirsContext from 'merchant/views/AccountAndSettings/InternationalSettings/hooks/useFirsContext';

jest.mock('merchant/views/AccountAndSettings/InternationalSettings/hooks/useFirsContext', () => ({
  __esModule: true,
  default: jest.fn(() => ({})),
}));

jest.mock('merchant/views/AccountAndSettings/InternationalSettings/services');

afterEach(() => {
  jest.clearAllMocks();
});

export const mockContextData = (data) => {
  useFirsContext.mockReturnValue({ isRequestFirsEnabled: true, ...data });
};
