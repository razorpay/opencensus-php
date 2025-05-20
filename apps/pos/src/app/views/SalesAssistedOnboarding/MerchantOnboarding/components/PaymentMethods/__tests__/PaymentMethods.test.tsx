import React from 'react';
import { QueryClient } from '@tanstack/react-query';
import { render, screen, server, waitFor } from 'apps/pos/src/services/test/test-utils';
import PaymentMethods from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/PaymentMethods/PaymentMethods';
import { getMockUseOnboardingContext } from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/PaymentMethods/__tests__/mocks/fixtures';
import useOnboardingContext from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext';
import { mockProps } from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/PaymentMethods/__tests__/mocks/fixtures';

const mockSubmit = jest.fn();
const props = {
  ...mockProps,
  onFileUploadChange: jest.fn(),
  onFieldCheckboxChange: jest.fn(),
  onFieldInputChange: jest.fn(),
  onFormSubmitClick: mockSubmit,
};

jest.mock(
  'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext',
  () => {
    return { __esModule: true, default: jest.fn() };
  },
);
jest.setTimeout(30000);
const queryClient = new QueryClient();
const renderApp = (props?: any) => {
  render(<PaymentMethods brandEmi={false} addedBrands={false} nach={false} {...props} />);
};

describe('PaymentMethods', () => {
  afterAll(() => jest.clearAllMocks());
  afterEach(() => {
    jest.clearAllMocks();
    queryClient.clear();
  });
  beforeEach(() => {
    server.listen();
  });
  const mockContext = getMockUseOnboardingContext();
  const updateModularConfig = jest.fn();
  const handleProceedToNextComponent = jest.fn();
  const refetchModularConfig = jest.fn();
  const getStepConfigStepSlug = jest.fn().mockReturnValue({
    slug: 'paymentMethods',
    components: [
      {
        slug: 'nachForm',
      },
    ],
  });
  (useOnboardingContext as jest.Mock).mockReturnValue({
    ...mockContext,
    handlers: {
      ...mockContext.handlers,
      updateModularConfig,
      handleProceedToNextComponent,
      refetchModularConfig,
      getStepConfigStepSlug,
    },
  });
  afterEach(() => {
    server.resetHandlers();
    queryClient.clear();
  });

  afterAll(() => {
    server.close();
  });

  test('should render PaymentMethodForm by default', () => {
    renderApp({});
    expect(screen.getByText(/Select Onboarding Model/i)).toBeInTheDocument();
  });

  test('should render NACHForm when nach prop is true', () => {
    renderApp({ nach: true });
    expect(screen.getByText(/Upload NACH Form/i)).toBeInTheDocument();
  });

  test('should render BrandEMIFormContainer when brandEmi prop is true', () => {
    renderApp({ brandEmi: true });
    expect(screen.getByText(/Brand Information Form/i)).toBeInTheDocument();
  });

  test('should render AddedBrandInfoContainer when addedBrands prop is true', () => {
    renderApp({ addedBrands: true });
    expect(screen.getByRole('button', { name: /add new brand/i })).toBeInTheDocument();
  });

  test('should render error boundary fallback on error', async () => {
    (useOnboardingContext as jest.Mock).mockReturnValue({
      ...mockContext,
      handlers: {},
    });
    renderApp({});

    await waitFor(() => {
      expect(screen.getByText('Something went wrong!')).toBeInTheDocument();
      expect(
        screen.getByText('We are facing some issues. Please try again later.'),
      ).toBeInTheDocument();
    });
  });
});
