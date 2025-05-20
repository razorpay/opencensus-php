import React from 'react';
import { render, screen, userEvent } from 'apps/pos/src/services/test/test-utils';
import useOnboardingContext from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext';
import {
  getMockModularResponseWithDeviceStep,
  getMockUseOnboardingContext,
} from './mocks/fixtures';
import { MODULAR_DEVICE_FIELDS } from 'apps/pos/src/app/types/DeviceSelection';
import { AvailableComponents } from 'apps/pos/src/app/types/common';
import DevicePaymentMethodContainer from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/DeviceOrdering/DevicePaymentMethods/DevicePaymentMethodContainer';
import { SpiltzContext } from '@federated/dashboards/payments/services/splitzService';
import { analyticsTypes, trackEvent } from 'apps/pos/src/services/analytics';

// Mock the analytics tracking
jest.mock('apps/pos/src/services/analytics', () => ({
  analyticsTypes: {
    ANALYTICS_EVENTS: { LINK: 'link' },
    ANALYTICS_ACTIONS: { CLICKED: 'clicked' },
    L1_FUNNEL_STAGE: { ORDER_PAYMENT_SCREEN: 'order_payment_screen' },
    L2_FUNNEL_STAGE: { CHECKOUT_CONFIRMATION: 'checkout_confirmation' },
  },
  trackEvent: jest.fn(),
}));

jest.mock(
  'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext',
  () => {
    return { __esModule: true, default: jest.fn() };
  },
);

const renderApp = () => {
  // Create a mock Splitz context
  const mockSplitzContext = {
    abExperiments: {
      pos_payment_link: {
        variables: {
          result: 'on',
        },
      },
    },
  };

  return render(
    <SpiltzContext.Provider value={mockSplitzContext}>
      <DevicePaymentMethodContainer />
    </SpiltzContext.Provider>,
  );
};

describe('DevicePaymentMethodContainer', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  const mockContext = getMockUseOnboardingContext();
  const updateModularConfig = jest.fn();
  const handleProceedToNextComponent = jest.fn();

  beforeEach(() => {
    (useOnboardingContext as jest.Mock).mockReturnValue({
      ...mockContext,
      handlers: {
        ...mockContext.handlers,
        updateModularConfig,
        handleProceedToNextComponent,
      },
      states: {
        ...mockContext.states,
        isUpdateModularLoading: false,
        merchantDetails: {
          contactPerson: {
            email: { value: 'test@example.com' },
            phone: { value: { number: '1234567890' } },
          },
        },
      },
    });
  });

  test('should return null when modular config is absent', async () => {
    (useOnboardingContext as jest.Mock).mockReturnValue({
      ...mockContext,
      states: {
        ...mockContext.states,
        modularConfig: null,
      },
    });
    renderApp();
    expect(screen.queryByText(/scan and pay/i)).not.toBeInTheDocument();
  });

  test('should render scan and pay card', async () => {
    (useOnboardingContext as jest.Mock).mockReturnValue({
      ...mockContext,
      states: {
        ...mockContext.states,
        modularConfig: getMockModularResponseWithDeviceStep({
          qrPaymentStatusField: 'pending',
        }).merchantModularOnboardingDetailsUpdateAsSales,
      },
      handlers: {
        ...mockContext.handlers,
        updateModularConfig,
        handleProceedToNextComponent,
      },
    });

    renderApp();
    const scanAndPayCard = screen.getByText(/scan and pay/i);
    await userEvent.click(scanAndPayCard);

    expect(trackEvent).toHaveBeenCalled();
    expect(updateModularConfig).toHaveBeenCalledWith(
      expect.objectContaining({
        [MODULAR_DEVICE_FIELDS.DEVICE_PAYMENT_OPTIONS_FIELD]: MODULAR_DEVICE_FIELDS.DEVICE_QR_CODE,
        [MODULAR_DEVICE_FIELDS.DEVICE_CREATE_QR_CODE_FIELD]: expect.any(Number),
        [MODULAR_DEVICE_FIELDS.DEVICE_ORDER_QR_AMOUNT]: expect.any(Number),
        [MODULAR_DEVICE_FIELDS.DEVICE_CANCEL_PAYMENT_LINK_FIELD]: expect.any(Number),
        [MODULAR_DEVICE_FIELDS.DEVICE_CHECK_FOR_ORDER_COMPLETION]: expect.any(Number),
      }),
    );

    const callback = updateModularConfig.mock.calls[0][0][MODULAR_DEVICE_FIELDS.MODULAR_CALLBACK];
    expect(typeof callback).toBe('function');

    callback();
    expect(handleProceedToNextComponent).toHaveBeenCalledWith({
      __typeName: 'custom_routing',
      routerConditions: {
        [AvailableComponents.DEVICE_PAYMENT]: true,
      },
    });
  });

  test('should render payment link card', async () => {
    (useOnboardingContext as jest.Mock).mockReturnValue({
      ...mockContext,
      states: {
        ...mockContext.states,
        modularConfig: getMockModularResponseWithDeviceStep({
          paymentLinkStatusField: 'pending',
          qrCodeStatusField: 'pending',
        }).merchantModularOnboardingDetailsUpdateAsSales,
        merchantDetails: {
          contactPerson: {
            email: { value: 'test@example.com' },
            phone: { value: { number: '1234567890' } },
          },
        },
      },
      handlers: {
        ...mockContext.handlers,
        updateModularConfig,
        handleProceedToNextComponent,
      },
    });

    renderApp();
    const paymentLinkCard = screen.getByText(/payment link/i);
    await userEvent.click(paymentLinkCard);

    expect(trackEvent).toHaveBeenCalled();
    expect(updateModularConfig).toHaveBeenCalledWith(
      expect.objectContaining({
        [MODULAR_DEVICE_FIELDS.DEVICE_PAYMENT_LINK_CONTACT_EMAIL]: 'test@example.com',
        [MODULAR_DEVICE_FIELDS.DEVICE_PAYMENT_LINK_CONTACT_MOBILE]: '1234567890',
        [MODULAR_DEVICE_FIELDS.DEVICE_PAYMENT_OPTIONS_FIELD]:
          MODULAR_DEVICE_FIELDS.DEVICE_PAYMENT_LINK,
        [MODULAR_DEVICE_FIELDS.DEVICE_GENERATE_PAYMENT_LINK_FIELD]: expect.any(Number),
        [MODULAR_DEVICE_FIELDS.DEVICE_ORDER_PAYMENT_LINK_AMOUNT_FIELD]: expect.any(Number),
        [MODULAR_DEVICE_FIELDS.DEVICE_CLOSE_QR_FIELD]: expect.any(Number),
        [MODULAR_DEVICE_FIELDS.DEVICE_CHECK_FOR_ORDER_COMPLETION]: expect.any(Number),
      }),
    );

    const callback = updateModularConfig.mock.calls[0][0][MODULAR_DEVICE_FIELDS.MODULAR_CALLBACK];
    expect(typeof callback).toBe('function');

    callback();
    expect(handleProceedToNextComponent).toHaveBeenCalledWith({
      __typeName: 'custom_routing',
      routerConditions: {
        [AvailableComponents.PAYMENT_LINK_METHOD]: true,
      },
    });
  });

  test('should directly move to payment link screen when payment status is paid', async () => {
    (useOnboardingContext as jest.Mock).mockReturnValue({
      ...mockContext,
      states: {
        ...mockContext.states,
        modularConfig: getMockModularResponseWithDeviceStep({
          paymentLinkStatusField: 'paid',
          qrCodeStatusField: 'pending',
        }).merchantModularOnboardingDetailsUpdateAsSales,
      },
      handlers: {
        ...mockContext.handlers,
        updateModularConfig,
        handleProceedToNextComponent,
      },
    });

    renderApp();
    const paymentLinkCard = screen.getByText(/payment link/i);
    await userEvent.click(paymentLinkCard);

    expect(trackEvent).toHaveBeenCalled();
    expect(updateModularConfig).not.toHaveBeenCalled();
    expect(handleProceedToNextComponent).toHaveBeenCalledWith({
      __typeName: 'custom_routing',
      routerConditions: {
        [AvailableComponents.PAYMENT_LINK_METHOD]: true,
      },
    });
  });

  test('should directly move to device order success screen when qr code is paid', async () => {
    (useOnboardingContext as jest.Mock).mockReturnValue({
      ...mockContext,
      states: {
        ...mockContext.states,
        modularConfig: getMockModularResponseWithDeviceStep({
          qrCodeStatusField: 'closed',
          qrPaymentStatusField: 'success',
        }).merchantModularOnboardingDetailsUpdateAsSales,
      },
      handlers: {
        ...mockContext.handlers,
        updateModularConfig,
        handleProceedToNextComponent,
      },
    });

    renderApp();
    const scanAndPayCard = screen.getByText(/scan and pay/i);
    await userEvent.click(scanAndPayCard);

    expect(trackEvent).toHaveBeenCalled();
    expect(updateModularConfig).not.toHaveBeenCalled();
    expect(handleProceedToNextComponent).toHaveBeenCalledWith({
      __typeName: 'custom_routing',
      routerConditions: {
        [AvailableComponents.DEVICE_PAYMENT]: true,
      },
    });
  });
});
