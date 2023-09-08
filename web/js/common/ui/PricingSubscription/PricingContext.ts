import { createContext, useContext } from 'react';

import type { MultiPaymentContextType } from 'common/ui/PricingSubscription/PricingSubscriptionProps.type';

export const INITIAL_PLAN = {
  icon: {
    src: '',
    alt: '',
  },
  title: '',
  isRecommended: '',
  id: '',
  description: '',
  monthlyPrice: 0,
  annualPrice: 0,
  button: {
    variant: 'primary',
    label: '',
  },
} as const;
const MultiPaymentContext = createContext<MultiPaymentContextType>({
  multiPaymentData: {
    planName: '',
    amount: 0,
    frequency: '',
    taxPercentage: 0,
    icon: '',
  },
  currentBalance: {
    data: {
      balance: 0,
    },
  },
  plans: { ...INITIAL_PLAN },
  checkoutPayment: {
    trackInstrumentation: () => {},
    togglePlan: 'monthly',
    setLoading: () => {},
    isLoading: false,
    setSelectedPlanId: () => {},
    handlePaymentSuccess: () => {},
    handlePaymentFailure: () => {},
    showNotificationToast: () => {},
    planAmount: 0,
    settlementBalance: 0,
    setCongratulatoryModal: () => {},
    setModalType: () => {},
    togglePaymentOptionModal: () => {},
    closeModal: () => {},
    currentBalance: { data: { balance: 0 } },
  },
});

const usePricingContext = (): MultiPaymentContextType => {
  return useContext(MultiPaymentContext);
};

export { usePricingContext, MultiPaymentContext };
