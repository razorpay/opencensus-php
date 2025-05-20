import React from 'react';
import { render, screen, userEvent } from 'apps/pos/src/services/test/test-utils';
import useOnboardingContext from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext';
import { getMockUseOnboardingContext } from './mocks/fixtures';
import { MODULAR_PRICING_FIELDS } from 'apps/pos/src/app/types/PaymentsAndService';
import BrandEMIFormContainer from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/PaymentMethods/BrandEMIForm/BrandEMIFormContainer';
import { getBrandEmiCcDcEnabledStatus } from 'apps/pos/src/app/utils/paymentsAndServices';

jest.mock('apps/pos/src/app/utils/paymentsAndServices', () => ({
  ...jest.requireActual('apps/pos/src/app/utils/paymentsAndServices'),
  getBrandEmiCcDcEnabledStatus: jest.fn(),
}));

jest.mock(
  'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext',
);

describe('BrandEMIFormContainer', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  const mockContext = getMockUseOnboardingContext();
  const updateModularConfig = jest.fn();
  const handleProceedToNextComponent = jest.fn();
  (useOnboardingContext as jest.Mock).mockReturnValue({
    ...mockContext,
    handlers: {
      ...mockContext.handlers,
      updateModularConfig,
      handleProceedToNextComponent,
    },
  });

  const renderComponent = (props = {}) => {
    return render(
      <BrandEMIFormContainer
        onBrandEmiFieldInputChange={jest.fn()}
        hasAddedBrandEMIData={false}
        isFormDisabled={false}
        {...props}
      />,
    );
  };

  test('should render disabled brand emi form is brand cc-dc fields are disabled', () => {
    (getBrandEmiCcDcEnabledStatus as jest.Mock).mockReturnValue(false);
    renderComponent();
    expect(screen.queryByText(/something went wrong/i)).not.toBeInTheDocument();
    const brandNameSelect = screen.getByRole('combobox', {
      name: /brand name required \*/i,
    });
    expect(brandNameSelect).toBeDisabled();
  });

  test('renders BrandEMIForm when modularConfig is present', () => {
    (getBrandEmiCcDcEnabledStatus as jest.Mock).mockReturnValue(true);
    renderComponent();
    expect(screen.queryByText(/something went wrong/i)).not.toBeInTheDocument();
  });

  test('calls updateModularConfig when a brand is selected', async () => {
    (getBrandEmiCcDcEnabledStatus as jest.Mock).mockReturnValue(true);
    renderComponent();
    const brandNameSelect = screen.getByRole('combobox', {
      name: /brand name required \*/i,
    });
    await userEvent.click(brandNameSelect);
    const option = screen.getByRole('option', { name: /bluestar/i });
    await userEvent.click(option);
    expect(updateModularConfig).toHaveBeenCalledWith({
      [MODULAR_PRICING_FIELDS.FETCH_FIELDS_FOR_BRAND]: 'bluestar',
      [MODULAR_PRICING_FIELDS.RESET_BRAND_DETAILS_FIELD]: expect.any(Number),
    });
  });

  test('calls updateModularConfig and proceeds to the next step on submit', async () => {
    (getBrandEmiCcDcEnabledStatus as jest.Mock).mockReturnValue(true);
    renderComponent();
    const typeOfStoreSelect = screen.getByRole('combobox', { name: /type of store required/i });
    await userEvent.click(typeOfStoreSelect);
    const storeType = screen.getByRole('option', { name: /multi brand outlet/i });
    await userEvent.click(storeType);
    const brandNameSelect = screen.getByRole('combobox', {
      name: /brand name required \*/i,
    });
    await userEvent.click(brandNameSelect);
    const option = screen.getByRole('option', { name: /bluestar/i });
    await userEvent.click(option);
    const saveBtn = screen.getByRole('button', { name: /save/i });
    expect(saveBtn).toBeEnabled();
    await userEvent.click(screen.getByRole('button', { name: /save/i }));
    expect(updateModularConfig).toHaveBeenLastCalledWith(
      expect.objectContaining({
        modular_callback: expect.any(Function),
      }),
    );
  });
});
