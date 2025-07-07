import React from 'react';

import CheckoutCta from 'apps/pos/src/app/views/SelfServe/OrderSummary/CheckoutCta';
import {
  MOCK_USER,
  MOCK_GTM,
  SUCCESSFUL_POS_ONBOARDING_RESPONSE,
  FAILED_POS_ONBOARDING_RESPONSE,
} from 'apps/pos/src/app/views/SelfServe/__tests__/mocks/fixtures';
import {
  createActvationCaseHandler,
  createOrderHandler,
  getPincodeInfoHandler,
  getProductPricingHandler,
} from 'apps/pos/src/app/views/SelfServe/__tests__/mocks/handlers';
import { PosStoreInitialState } from 'apps/pos/src/app/views/SelfServe/constants';
import { PosDeviceStoreProvider } from 'apps/pos/src/app/views/SelfServe/providers';
import * as posServices from 'apps/pos/src/app/views/SelfServe/services';
import { PosDeviceStoreState, ProductPlans } from 'apps/pos/src/app/views/SelfServe/types';
import {
  render,
  screen,
  server,
  userEvent,
  waitFor,
  waitForElementToBeRemoved,
} from 'apps/pos/src/services/test/test-utils';
import 'jest-location-mock';
import { isExperimentEnabled } from 'common/splitz/utils';

const initProps = {
  isDisabled: false,
  isLoading: false,
  showNotification: jest.fn(),
};

const CheckoutMock = jest.fn().mockImplementation((config) => {
  return {
    open: jest
      .fn()
      .mockImplementation(() =>
        config?.notes.merchant_id === 'fail_attempt'
          ? config?.modal?.ondismiss?.()
          : config?.handler?.(),
      ),
  };
});

jest.mock('common/utils/rzp-utils', () => ({
  ...(jest.requireActual('common/utils/rzp-utils') as Record<string, string>),
  isProductionEnv: () => true,
}));

jest.mock('common/splitz', () => ({
  ...(jest.requireActual('common/splitz') as Record<string, string>),
  useSplitzService: () => ({
    abExperiments: MOCK_GTM,
  }),
}));

jest.mock('common/splitz/utils', () => ({
  isExperimentEnabled: jest.fn(),
}));

const mockIsExperimentEnabled = isExperimentEnabled as jest.Mock;

describe('<CheckoutCta/>', () => {
  window.Razorpay = CheckoutMock;

  const renderApp = (props = initProps, user = MOCK_USER, isSkipCheckout = false) => {
    const MOCK_CART_ITEM = {
      code: 'mock-product',
      quantity: 1,
      plan: 'monthly' as ProductPlans,
    };

    const initialState = {
      ...PosStoreInitialState,
      cartItems: [MOCK_CART_ITEM],
    };

    render(
      <PosDeviceStoreProvider init={initialState as PosDeviceStoreState} user={user}>
        <CheckoutCta {...props} isSkipCheckout={isSkipCheckout} />
      </PosDeviceStoreProvider>,
    );
  };

  beforeEach(() => {
    server.use(
      createOrderHandler(),
      getProductPricingHandler(),
      createActvationCaseHandler(),
      getPincodeInfoHandler({ type: 'delivery_available' }),
    );
    window.EASY_ONBOARDING_URL = 'https://easy.razorpay.com';
  });

  test('should render checkout cta on screen', async () => {
    renderApp();
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    expect(screen.getByText('Confirm Address & Pay')).toBeVisible();
  });

  test('should render checkout as disabled if passed disabled as true', async () => {
    const newProps = {
      ...initProps,
      isDisabled: true,
    };
    renderApp(newProps);
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    await waitFor(() => {
      expect(screen.getByRole('button')).toBeDisabled();
    });
  });

  test('should call order create with correct payload', async () => {
    const user = {
      ...MOCK_USER,
      submitted: true,
      business_website: 'www.mock-website.com',
    };
    const createOrderServiceSpy = jest.spyOn(posServices, 'createOrder');
    renderApp(undefined, user);
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    await userEvent.click(screen.getByText('Confirm Address & Pay'));
    await waitFor(() => {
      expect(createOrderServiceSpy).toHaveBeenCalledWith({
        delivery_address: {
          address: 'test operation address',
          city: 'test operation city',
          country: 'IN',
          name: 'Test Name',
          phone_no: '1234567890',
          pin_code: '123456',
          state: 'test operation state',
        },
        items: [{ code: 'mock-product', count: 1, period: 'monthly' }],
      });
    });
  });

  test('should call checkout js with correct config', async () => {
    const user = {
      ...MOCK_USER,
      submitted: true,
      business_website: 'www.mock-website.com',
    };
    renderApp(undefined, user);
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    await userEvent.click(screen.getByText('Confirm Address & Pay'));
    await waitFor(() => {
      expect(CheckoutMock).toHaveBeenCalledWith(
        expect.objectContaining({
          description: '18% GST included',
          notes: {
            type: 'Pos Device Store',
            merchant_id: 'mock-user-id',
            device_order_id: 'mock-order-id',
          },
          order_id: 'order_mock-order-id',
          name: 'Razorpay POS',
          theme: {
            color: '#3005BF2',
          },
        }),
      );
    });
  });

  test('should call sucess callback post checkout', async () => {
    window.location.assign = jest.fn();

    const user = {
      ...MOCK_USER,
      submitted: true,
      business_website: 'www.mock-website.com',
    };
    renderApp(undefined, user);
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    await userEvent.click(screen.getByText('Confirm Address & Pay'));
    await waitFor(() => {
      expect(window.location.assign).toHaveBeenCalledWith('/app/pos/order-status/mock-order-id');
    });
  });

  test('should show error message if order create fails', async () => {
    server.use(createOrderHandler(false));
    const user = {
      ...MOCK_USER,
      submitted: true,
      business_website: 'www.mock-website.com',
    };
    renderApp(undefined, user);
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    await userEvent.click(screen.getByText('Confirm Address & Pay'));
    await waitFor(
      () => {
        expect(screen.getByText('Something went wrong. Please try again')).toBeVisible();
      },
      {
        timeout: 5000,
      },
    );
  });

  test('should call error handler if checkout fails', async () => {
    const user = {
      ...MOCK_USER,
      submitted: true,
      business_website: 'www.mock-website.com',
      merchant: {
        id: 'fail_attempt',
      },
    };
    renderApp(undefined, user);
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    await userEvent.click(screen.getByText('Confirm Address & Pay'));
    await waitFor(
      () => {
        expect(screen.getByText('Payment Failed!')).toBeVisible();
      },
      {
        timeout: 5000,
      },
    );
  });

  test('should show terms and condition agreement before checkout ', async () => {
    const user = {
      ...MOCK_USER,
      submitted: true,
      business_website: 'www.mock-website.com',
      created_at: 1696398131,
    };
    renderApp(undefined, user);
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    expect(screen.getByText(/By proceeding to pay, I agree to Razorpay POS/)).toBeVisible();
    expect(screen.getByText(/Terms & Conditions/)).toBeVisible();
    expect(screen.getByTestId('pos-terms-and-conditions-link')).toHaveAttribute(
      'href',
      'https://razorpay.com/s/pos-machine-terms-of-use',
    );

    expect(screen.getByText(/Privacy Policy/)).toBeVisible();
    expect(screen.getByTestId('pos-privacy-policy-link')).toHaveAttribute(
      'href',
      'https://razorpay.com/s/pos-machine-privacy-policy',
    );
  });

  test('should open additional details modal if L2 is not submitted and redirect to easy dashboard', async () => {
    window.location.assign = jest.fn();
    const user = {
      ...MOCK_USER,
    };
    renderApp(undefined, user);
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    await userEvent.click(screen.getByText('Confirm Address & Pay'));
    await waitFor(() => {
      expect(
        screen.getByText(
          'Please add few more details in order to complete your order. Without these, we won’t be able to process your POS order.',
        ),
      ).toBeVisible();
    });

    await userEvent.click(screen.getByText('Add Details'));
    await waitFor(() => {
      expect(window.location.assign).toHaveBeenCalledWith(
        'https://easy.razorpay.com/onboarding/l2',
      );
    });
  });

  test('should open additional details modal if no online presence and no shop images, also should redirect to easy dashboard', async () => {
    window.location.assign = jest.fn();
    const user = {
      ...MOCK_USER,
      submitted: '1',
      pos_activation_status: null,
    };
    renderApp(undefined, user);
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    await userEvent.click(screen.getByText('Confirm Address & Pay'));
    await waitFor(() => {
      expect(
        screen.getByText(
          'Please add few more details in order to complete your order. Without these, we won’t be able to process your POS order.',
        ),
      ).toBeVisible();
    });

    await userEvent.click(screen.getByText('Add Details'));
    await waitFor(() => {
      expect(window.location.assign).toHaveBeenCalledWith(
        'https://easy.razorpay.com/onboarding/pos/store-details?posselfserve=true',
      );
    });
  });

  test('should redirect to easy onboarding with query params if l2 is not submitted with no pos intent and but online presence is there', async () => {
    window.location.assign = jest.fn();
    const user = {
      ...MOCK_USER,
    };

    user.merchant_business_detail.website_details.physical_store = false;
    renderApp(undefined, user);
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    await userEvent.click(screen.getByText('Confirm Address & Pay'));
    await waitFor(() => {
      expect(
        screen.getByText(
          'Please add few more details in order to complete your order. Without these, we won’t be able to process your POS order.',
        ),
      ).toBeVisible();
    });

    await userEvent.click(screen.getByText('Add Details'));
    await waitFor(() => {
      expect(window.location.assign).toHaveBeenCalledWith(
        'https://easy.razorpay.com/onboarding/l2?intent=pos&posselfserve=true',
      );
    });
  });

  test('should redirect to easy onboarding with query params if no online presence and no shop images and no pos intent', async () => {
    window.location.assign = jest.fn();
    const user = {
      ...MOCK_USER,
      submitted: '1',
    };

    user.merchant_business_detail.website_details.physical_store = false;
    renderApp(undefined, user);
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    await userEvent.click(screen.getByText('Confirm Address & Pay'));
    await waitFor(() => {
      expect(
        screen.getByText(
          'Please add few more details in order to complete your order. Without these, we won’t be able to process your POS order.',
        ),
      ).toBeVisible();
    });

    await userEvent.click(screen.getByText('Add Details'));
    await waitFor(() => {
      expect(window.location.assign).toHaveBeenCalledWith(
        'https://easy.razorpay.com/onboarding/pos/store-details?intent=pos&posselfserve=true',
      );
    });
  });

  test('should not call activation api if user has pos intent and l2 is submitted and online presence is there and NO shop images', async () => {
    window.location.assign = jest.fn();
    const newUser = {
      ...MOCK_USER,
      business_website: 'www.mock-website.com',
      pos_activation_status: 'under_review',
      submitted: 1,
      merchant_business_detail: {
        website_details: {
          physical_store: false,
        },
      },
      documents: {
        shop_front: ['some-image-url'],
        shop_interior: ['some-image-url'],
      },
    };
    renderApp(undefined, newUser);
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    await userEvent.click(screen.getByText('Confirm Address & Pay'));
    const activationServiceSpy = jest.spyOn(posServices, 'createActvationCase');

    await waitFor(() => {
      expect(CheckoutMock).toHaveBeenCalledWith(
        expect.objectContaining({
          description: '18% GST included',
          notes: {
            type: 'Pos Device Store',
            merchant_id: 'mock-user-id',
            device_order_id: 'mock-order-id',
          },
          order_id: 'order_mock-order-id',
          name: 'Razorpay POS',
          theme: {
            color: '#3005BF2',
          },
        }),
      );
    });

    expect(activationServiceSpy).not.toHaveBeenCalled();
  });

  test('should call activation api with correct payload if user has NO pos intent and l2 is submitted and online presence is there and NO shop images', async () => {
    window.location.assign = jest.fn();
    const newUser = {
      ...MOCK_USER,
      business_website: 'www.mock-website.com',
      pos_activation_status: null,
      submitted: 1,
      merchant_business_detail: {
        website_details: {
          physical_store: false,
        },
      },
      documents: {},
    };
    renderApp(undefined, newUser);
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    await userEvent.click(screen.getByText('Confirm Address & Pay'));
    const activationServiceSpy = jest.spyOn(posServices, 'createActvationCase');
    await waitFor(() => {
      expect(activationServiceSpy).toHaveBeenCalled();
    });
    await waitFor(() => {
      expect(CheckoutMock).toHaveBeenCalledWith(
        expect.objectContaining({
          description: '18% GST included',
          notes: {
            type: 'Pos Device Store',
            merchant_id: 'mock-user-id',
            device_order_id: 'mock-order-id',
          },
          order_id: 'order_mock-order-id',
          name: 'Razorpay POS',
          theme: {
            color: '#3005BF2',
          },
        }),
      );
    });
  });

  test('should call activation api with correct payload if user has online presence and has some online activation status but no shop images', async () => {
    window.location.assign = jest.fn();
    const newUser = {
      ...MOCK_USER,
      business_website: 'www.mock-website.com',
      pos_activation_status: null,
      submitted: 1,
      activation_status: 'under_review',
      merchant_business_detail: {
        website_details: {
          physical_store: true,
        },
      },
      documents: {},
    };
    renderApp(undefined, newUser);
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    await userEvent.click(screen.getByText('Confirm Address & Pay'));
    const activationServiceSpy = jest.spyOn(posServices, 'createActvationCase');
    await waitFor(() => {
      expect(activationServiceSpy).toHaveBeenCalled();
    });
    await waitFor(() => {
      expect(CheckoutMock).toHaveBeenCalledWith(
        expect.objectContaining({
          description: '18% GST included',
          notes: {
            type: 'Pos Device Store',
            merchant_id: 'mock-user-id',
            device_order_id: 'mock-order-id',
          },
          order_id: 'order_mock-order-id',
          name: 'Razorpay POS',
          theme: {
            color: '#3005BF2',
          },
        }),
      );
    });
  });

  test('should open checkout confirmation box and trigger checkout once user confirms', async () => {
    const user = {
      ...MOCK_USER,
      submitted: true,
      business_website: 'www.mock-website.com',
    };
    const createOrderServiceSpy = jest.spyOn(posServices, 'createOrder');
    renderApp(undefined, user, true);
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    await userEvent.click(screen.getByText('Confirm Address & Pay'));
    await waitFor(
      () => {
        expect(screen.getByText('Confirm Order')).toBeVisible();
      },
      {
        timeout: 5000,
      },
    );
    await userEvent.click(screen.getByTestId('pos-checkout-confim-modal-confirm'));
    await waitFor(() => {
      expect(createOrderServiceSpy).toHaveBeenCalledWith({
        delivery_address: {
          address: 'test operation address',
          city: 'test operation city',
          country: 'IN',
          name: 'Test Name',
          phone_no: '1234567890',
          pin_code: '123456',
          state: 'test operation state',
        },
        items: [{ code: 'mock-product', count: 1, period: 'monthly' }],
      });
    });
  });

  test('should show error if city not available in gtm city list', async () => {
    server.use(getPincodeInfoHandler({ type: 'delivery_unavailable' }));
    const user = {
      ...MOCK_USER,
      submitted: true,
      business_website: 'www.mock-website.com',
    };
    renderApp(undefined, user);
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    await userEvent.click(screen.getByText('Confirm Address & Pay'));
    await waitFor(
      () => {
        expect(screen.getByText('Pincode not serviceable! Arriving Soon.')).toBeVisible();
      },
      {
        timeout: 5000,
      },
    );
  });

  test('should call initiatePosOnboarding if isPOSEnabledForAPIMerchant is true', async () => {
    const user = {
      ...MOCK_USER,
      submitted: true,
      business_website: 'www.mock-website.com',
      pos_activation_status: null,
    };
    mockIsExperimentEnabled.mockImplementation(() => true);

    const initiatePosOnboardingSpy = jest
      .spyOn(posServices, 'initiatePosOnboarding')
      .mockResolvedValue(SUCCESSFUL_POS_ONBOARDING_RESPONSE);
    renderApp(undefined, user);
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    await userEvent.click(screen.getByText('Confirm Address & Pay'));
    await waitFor(() => {
      expect(initiatePosOnboardingSpy).toHaveBeenCalled();
    });
  });

  test('should throw error if initiatePosOnboarding fails', async () => {
    const user = {
      ...MOCK_USER,
      submitted: true,
      business_website: 'www.mock-website.com',
    };
    mockIsExperimentEnabled.mockImplementation(() => true);

    jest
      .spyOn(posServices, 'initiatePosOnboarding')
      .mockResolvedValue(FAILED_POS_ONBOARDING_RESPONSE);
    renderApp(undefined, user);
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    await userEvent.click(screen.getByText('Confirm Address & Pay'));
    await waitFor(
      () => {
        expect(screen.getByText('Something went wrong. Please try again')).toBeVisible();
      },
      {
        timeout: 5000,
      },
    );
  });
});
