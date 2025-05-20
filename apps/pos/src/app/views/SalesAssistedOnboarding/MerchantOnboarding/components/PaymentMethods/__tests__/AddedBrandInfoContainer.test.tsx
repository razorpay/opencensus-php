import React from 'react';
import { render, screen, userEvent, waitFor } from 'apps/pos/src/services/test/test-utils';
import useOnboardingContext from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext';
import AddedBrandInfoContainer from '../BrandEMIForm/AddedBrandInfoContainer';
import { getMockUseOnboardingContext } from './mocks/fixtures';
import { MODULAR_PRICING_FIELDS } from 'apps/pos/src/app/types/PaymentsAndService';
import { AvailableComponents } from 'apps/pos/src/app/types/common';
import { getBrandEmiCcDcEnabledStatus } from 'apps/pos/src/app/utils/paymentsAndServices';

jest.mock('apps/pos/src/app/utils/paymentsAndServices', () => ({
  ...jest.requireActual('apps/pos/src/app/utils/paymentsAndServices'),
  getBrandEmiCcDcEnabledStatus: jest.fn(),
}));

jest.mock(
  'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext',
  () => {
    return { __esModule: true, default: jest.fn() };
  },
);

const renderApp = (props) => {
  return render(<AddedBrandInfoContainer {...props} />);
};

describe('AddedBrandInfoContainer', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  (getBrandEmiCcDcEnabledStatus as jest.Mock).mockReturnValue(true);
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

  test('should call addBrandHandler when add button is clicked', async () => {
    renderApp({ isFormDisabled: false });
    await userEvent.click(screen.getByRole('button', { name: /add new brand/i }));
    expect(updateModularConfig).toHaveBeenCalledWith({
      [MODULAR_PRICING_FIELDS.RESET_BRAND_DETAILS_FIELD]: expect.any(Number),
      [MODULAR_PRICING_FIELDS.MODULAR_CALLBACK]: expect.any(Function),
    });
    const modularCallback =
      updateModularConfig.mock.calls[0][0][MODULAR_PRICING_FIELDS.MODULAR_CALLBACK];
    expect(modularCallback).toBeInstanceOf(Function);
    modularCallback();
    expect(handleProceedToNextComponent).toHaveBeenCalledWith({
      __typeName: 'custom_routing',
      routerConditions: {
        [AvailableComponents.BRAND_EMI_FORM]: true,
      },
    });
  });

  test('Should disable Add New Brand button when brand emi cc and dc fields are disabled', async () => {
    (getBrandEmiCcDcEnabledStatus as jest.Mock).mockReturnValue(false);
    renderApp({ isFormDisabled: false });
    const addNewBrandButton = screen.getByRole('button', { name: /add new brand/i });
    expect(addNewBrandButton).toBeDisabled();
  });

  test('should call removeBrandHandler when remove button is clicked', async () => {
    (getBrandEmiCcDcEnabledStatus as jest.Mock).mockReturnValue(true);
    renderApp({ isFormDisabled: false });
    const removeBtn = screen.getByRole('button', { name: /remove-icon/i });
    await userEvent.click(removeBtn);

    expect(updateModularConfig).toHaveBeenCalledWith({
      [MODULAR_PRICING_FIELDS.REMOVE_BRAND_DETAILS_FIELD]: true,
      [MODULAR_PRICING_FIELDS.BRAND_NAME_FIELD]: expect.any(String),
    });
  });

  test('should call submitHandler when submit button is clicked', async () => {
    renderApp({ isFormDisabled: false });
    await userEvent.click(screen.getByRole('button', { name: /save all/i }));
    expect(handleProceedToNextComponent).toHaveBeenCalledWith({
      __typeName: 'custom_routing',
      routerConditions: {
        [AvailableComponents.PAYMENT_METHODS]: true,
      },
    });
  });
});
