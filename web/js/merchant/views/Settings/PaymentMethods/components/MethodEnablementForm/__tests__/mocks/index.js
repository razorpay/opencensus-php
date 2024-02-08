import useFormContext from 'merchant/views/Settings/PaymentMethods/components/MethodEnablementForm/hooks/useFormContext';

jest.mock(
  'merchant/views/Settings/PaymentMethods/components/MethodEnablementForm/hooks/useFormContext',
  () => ({
    __esModule: true,
    default: jest.fn(() => ({})),
  }),
);

jest.mock('merchant/views/Settings/PaymentMethods/components/MethodEnablementForm/services');

afterEach(() => {
  jest.clearAllMocks();
});

export const mockContextData = (data) => {
  useFormContext.mockReturnValue({
    setIsLoading: jest.fn(),
    onTabClick: jest.fn(),
    setInitialValues: jest.fn(),
    setApiData: jest.fn(),
    ...data,
  });
};
