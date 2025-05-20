import React from 'react';
import { render, screen, userEvent, waitFor } from 'apps/pos/src/services/test/test-utils';
import useOnboardingContext from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext';
import {
  getMockModularResponseWithDeviceStep,
  getMockUseOnboardingContext,
} from './mocks/fixtures';
import PaymentLinkMethodContainer from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/DeviceOrdering/DevicePaymentMethods/PaymentLinkMethod';
import { MODULAR_DEVICE_FIELDS } from 'apps/pos/src/app/types/DeviceSelection';
import { AvailableComponents } from 'apps/pos/src/app/types/common';

jest.mock(
  'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext',
  () => {
    return { __esModule: true, default: jest.fn() };
  },
);

document.execCommand = jest.fn(() => true);

const renderApp = () => {
  return render(<PaymentLinkMethodContainer />);
};

describe('PaymentLinkMethodContainer', () => {
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

  test('should return null when modular config is absent', async () => {
    (useOnboardingContext as jest.Mock).mockReturnValueOnce({
      ...mockContext,
      states: {
        ...mockContext.states,
        modularConfig: null,
      },
    });
    renderApp();
    expect(
      screen.queryByRole('button', {
        name: /generate link/i,
      }),
    ).not.toBeInTheDocument();
  });

  test('should return payment link method component when modular config is present', async () => {
    renderApp();
    expect(
      screen.getByRole('button', {
        name: /generate link/i,
      }),
    ).toBeInTheDocument();
  });

  test('should call generate payment link with correct payload', async () => {
    renderApp();
    const generateLinkButton = screen.getByRole('button', {
      name: /generate link/i,
    });
    await userEvent.click(generateLinkButton);
    expect(updateModularConfig).toHaveBeenCalledWith({
      [MODULAR_DEVICE_FIELDS.DEVICE_GENERATE_PAYMENT_LINK_FIELD]: expect.any(Number),
      [MODULAR_DEVICE_FIELDS.DEVICE_CHECK_FOR_ORDER_COMPLETION]: expect.any(Number),
      [MODULAR_DEVICE_FIELDS.DEVICE_PAYMENT_LINK_CONTACT_EMAIL]: expect.any(String),
      [MODULAR_DEVICE_FIELDS.DEVICE_PAYMENT_LINK_CONTACT_MOBILE]: expect.any(String),
      [MODULAR_DEVICE_FIELDS.DEVICE_ORDER_PAYMENT_LINK_AMOUNT_FIELD]: expect.any(Number),
    });
  });

  test('should call generate new payment link with correct payload', async () => {
    (useOnboardingContext as jest.Mock).mockReturnValueOnce({
      ...mockContext,
      states: {
        ...mockContext.states,
        modularConfig: getMockModularResponseWithDeviceStep({
          paymentLinkStatusField: 'expired',
        }).merchantModularOnboardingDetailsUpdateAsSales,
      },
      handlers: {
        ...mockContext.handlers,
        updateModularConfig,
        handleProceedToNextComponent,
      },
    });
    renderApp();
    const generateNewLinkButton = screen.getByRole('button', {
      name: /generate new link/i,
    });
    await userEvent.click(generateNewLinkButton);
    expect(updateModularConfig).toHaveBeenCalledWith({
      [MODULAR_DEVICE_FIELDS.DEVICE_GENERATE_PAYMENT_LINK_FIELD]: expect.any(Number),
      [MODULAR_DEVICE_FIELDS.DEVICE_CHECK_FOR_ORDER_COMPLETION]: expect.any(Number),
    });
  });

  test('should call check payment status with correct payload', async () => {
    (useOnboardingContext as jest.Mock).mockReturnValueOnce({
      ...mockContext,
      states: {
        ...mockContext.states,
        modularConfig: getMockModularResponseWithDeviceStep({
          paymentLinkStatusField: 'created',
        }).merchantModularOnboardingDetailsUpdateAsSales,
      },
      handlers: {
        ...mockContext.handlers,
        updateModularConfig,
        handleProceedToNextComponent,
      },
    });
    renderApp();
    const checkPaymentStatusBtn = screen.getByRole('button', {
      name: /check payment status/i,
    });
    await userEvent.click(checkPaymentStatusBtn);
    expect(updateModularConfig).toHaveBeenCalledWith({
      [MODULAR_DEVICE_FIELDS.DEVICE_CHECK_PAYMENT_LINK_STATUS_FIELD]: expect.any(Number),
      [MODULAR_DEVICE_FIELDS.DEVICE_CHECK_FOR_ORDER_COMPLETION]: expect.any(Number),
    });
  });

  test('should call resend link with correct payload', async () => {
    let mockUpdateModularConfig = jest.fn((payload) => {
      payload[MODULAR_DEVICE_FIELDS.MODULAR_CALLBACK]();
    });

    (useOnboardingContext as jest.Mock).mockReturnValueOnce({
      ...mockContext,
      states: {
        ...mockContext.states,
        modularConfig: getMockModularResponseWithDeviceStep({
          paymentLinkStatusField: 'created',
        }).merchantModularOnboardingDetailsUpdateAsSales,
      },
      handlers: {
        ...mockContext.handlers,
        updateModularConfig: mockUpdateModularConfig,
        handleProceedToNextComponent,
      },
    });
    renderApp();
    const resendLinkBtn = screen.getByRole('button', { name: /re-send link/i });
    await userEvent.click(resendLinkBtn);

    expect(mockUpdateModularConfig).toHaveBeenCalledWith({
      [MODULAR_DEVICE_FIELDS.DEVICE_RESEND_PAYMENT_LINK_FIELD]: expect.any(Number),
      [MODULAR_DEVICE_FIELDS.DEVICE_CHECK_FOR_ORDER_COMPLETION]: expect.any(Number),
      [MODULAR_DEVICE_FIELDS.MODULAR_CALLBACK]: expect.any(Function),
    });

    await waitFor(() => {
      expect(screen.getByText(/Payment link re-sent successfully/i)).toBeInTheDocument();
    });
  });

  test('should be able to copy link', async () => {
    (useOnboardingContext as jest.Mock).mockReturnValueOnce({
      ...mockContext,
      states: {
        ...mockContext.states,
        modularConfig: getMockModularResponseWithDeviceStep({
          paymentLinkStatusField: 'created',
        }).merchantModularOnboardingDetailsUpdateAsSales,
      },
      handlers: {
        ...mockContext.handlers,
        updateModularConfig,
        handleProceedToNextComponent,
      },
    });
    renderApp();
    const copyLinkBtn = screen.getByRole('button', { name: /copy link/i });
    await userEvent.click(copyLinkBtn);

    await waitFor(() => {
      expect(screen.getByText(/Link copied successfully/i)).toBeInTheDocument();
    });
  });

  test('should move to order success screen if status is paid', async () => {
    (useOnboardingContext as jest.Mock).mockReturnValueOnce({
      ...mockContext,
      states: {
        ...mockContext.states,
        modularConfig: getMockModularResponseWithDeviceStep({
          paymentLinkStatusField: 'paid',
        }).merchantModularOnboardingDetailsUpdateAsSales,
      },
      handlers: {
        ...mockContext.handlers,
        updateModularConfig,
        handleProceedToNextComponent,
      },
    });
    renderApp();
    const continueBtn = screen.getByRole('button', {
      name: /Continue to next step/i,
    });
    await userEvent.click(continueBtn);
    expect(handleProceedToNextComponent).toHaveBeenCalledWith({
      __typeName: 'custom_routing',
      routerConditions: {
        [AvailableComponents.DEVICE_PAYMENT]: true,
      },
    });
  });
});
