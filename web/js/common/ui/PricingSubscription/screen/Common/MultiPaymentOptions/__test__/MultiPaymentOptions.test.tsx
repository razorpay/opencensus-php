import React from 'react';
import { userEvent, render, screen, waitFor } from 'test-utils';

import Unchecked from 'assets/pricing-bundle/Unchecked.jpg';
import { TogglePlanValue } from 'common/ui/PricingSubscription/PricingBundleCommon';
import { INITIAL_PLAN, MultiPaymentContext } from 'common/ui/PricingSubscription/PricingContext';
import { PAYMENT_TYPE, supportedPaymentMode } from 'common/ui/PricingSubscription/constants';
import MultiPaymentModal, {
  MultiPaymentOptions,
  PaymentOptionCard,
} from 'common/ui/PricingSubscription/screen/Common/MultiPaymentOptions/MultiPaymentOptions';

const MultiPaymentOptionsProps = {
  setSelectedPaymentMode: jest.fn(),
};

describe('Test: MultiPaymentOptions component', () => {
  const mockContextData = {
    checkoutPayment: {
      trackInstrumentation: jest.fn(),
      togglePlan: TogglePlanValue.monthly,
      setLoading: jest.fn(),
      isLoading: false,
      setSelectedPlanId: jest.fn(),
      handlePaymentSuccess: jest.fn(),
      handlePaymentFailure: jest.fn(),
      showNotificationToast: jest.fn(),
      planAmount: 0,
      settlementBalance: 0,
      setCongratulatoryModal: jest.fn(),
      setModalType: jest.fn(),
      togglePaymentOptionModal: jest.fn(),
      closeModal: jest.fn(),
      currentBalance: { data: { balance: 0 } },
    },
    currentBalance: { data: { balance: 100 } },
    plans: { ...INITIAL_PLAN },
    multiPaymentData: {
      icon: Unchecked,
      planName: 'Plan Name',
      amount: 5000,
      frequency: 'monthly',
      taxPercentage: 18,
    },
  };

  test('should render Multi payment Modal content container', () => {
    render(
      <MultiPaymentContext.Provider value={{ ...mockContextData }}>
        <MultiPaymentOptions {...MultiPaymentOptionsProps} />
      </MultiPaymentContext.Provider>,
    );
    const parentContainer = screen.getByTestId('MultiPaymentOptionsContainer');
    expect(parentContainer).toBeInTheDocument();
  });
  test('should render plan image', () => {
    render(
      <MultiPaymentContext.Provider value={{ ...mockContextData }}>
        <MultiPaymentOptions {...MultiPaymentOptionsProps} />
      </MultiPaymentContext.Provider>,
    );
    const ImageContainer = screen.getByTestId('renderImage');
    expect(ImageContainer).toBeInTheDocument();
    expect(ImageContainer.querySelector('img')).toBeInTheDocument();
  });
  test('should render plan image', () => {
    render(
      <MultiPaymentContext.Provider value={{ ...mockContextData }}>
        <MultiPaymentOptions {...MultiPaymentOptionsProps} />
      </MultiPaymentContext.Provider>,
    );
    const planName = screen.getByText(mockContextData.multiPaymentData.planName);
    expect(planName).toBeInTheDocument();
  });
  test('should render sub text', () => {
    render(
      <MultiPaymentContext.Provider value={{ ...mockContextData }}>
        <MultiPaymentOptions {...MultiPaymentOptionsProps} />
      </MultiPaymentContext.Provider>,
    );
    const subText = screen.getByText('Select how you want to pay :');
    expect(subText).toBeInTheDocument();
  });
  test('should render GST Text', () => {
    render(
      <MultiPaymentContext.Provider value={{ ...mockContextData }}>
        <MultiPaymentOptions {...MultiPaymentOptionsProps} />
      </MultiPaymentContext.Provider>,
    );
    const amount = screen.getByText(
      `Including ${mockContextData.multiPaymentData.taxPercentage}% GST`,
    );
    expect(amount).toBeInTheDocument();
  });
  test('should render plan type', () => {
    render(
      <MultiPaymentContext.Provider value={{ ...mockContextData }}>
        <MultiPaymentOptions {...MultiPaymentOptionsProps} />
      </MultiPaymentContext.Provider>,
    );
    const amount = screen.getByText(
      mockContextData.multiPaymentData.frequency?.charAt(0)?.toUpperCase() +
        mockContextData.multiPaymentData.frequency?.slice(1),
    );
    expect(amount).toBeInTheDocument();
  });
  test('should render proceed button', async () => {
    render(
      <MultiPaymentContext.Provider value={{ ...mockContextData }}>
        <MultiPaymentOptions {...MultiPaymentOptionsProps} />
      </MultiPaymentContext.Provider>,
    );
    const handleCheckoutPayment = jest.fn();
    const proceedButton = screen.getByText('Proceed');
    await userEvent.click(proceedButton);
    waitFor(() => {
      expect(handleCheckoutPayment).toBeCalledWith({
        ...mockContextData.checkoutPayment,
        plans: mockContextData.plans,
        type: PAYMENT_TYPE.INTERNAL,
      });
    });
  });
});

describe('Test: MultiPaymentModal component', () => {
  const mockProps = {
    isOpen: true,
    togglePaymentOptionModal: jest.fn(),
    zIndex: 9999 as const,
    setSelectedPaymentMode: jest.fn(),
  };

  test('calls togglePaymentOptionModal when modal is dismissed', async () => {
    const togglePaymentOptionModal = jest.fn();
    render(<MultiPaymentModal {...mockProps} />);
    const dismissButton = screen.getByRole('button', {
      name: 'Close',
    });
    await userEvent.click(dismissButton);
    waitFor(() => {
      expect(togglePaymentOptionModal).toHaveBeenCalledWith(false);
    });
  });
});

describe('Test: PaymentOptionCard component', () => {
  const mockedSetIsRadioClick = jest.fn();
  const mockedSetSelectedPaymentMode = jest.fn();
  const mockProps = {
    handlePaymentMethod: (paymentMethod) => {
      return () => {
        mockedSetIsRadioClick(paymentMethod);
        mockedSetSelectedPaymentMode(paymentMethod);
      };
    },
    isRadioClick: PAYMENT_TYPE.INTERNAL,
  };

  test.each(supportedPaymentMode(100))(
    'calls handlePaymentMethod when user click on PaymentOptionCard  ',
    async (item) => {
      render(<PaymentOptionCard {...mockProps} paymentOption={item} />);
      const paymentOptionCardContainer = screen.getByTestId(item.paymentType);
      expect(paymentOptionCardContainer).toBeInTheDocument();
      await userEvent.click(paymentOptionCardContainer);
      waitFor(() => {
        expect(mockProps.handlePaymentMethod).toHaveBeenCalledWith(item.paymentType);
      });
      expect(mockedSetIsRadioClick).toHaveBeenCalledWith(item.paymentType);
      expect(mockedSetSelectedPaymentMode).toHaveBeenCalledWith(item.paymentType);
    },
  );
});
