import { MOCK_MERCHANT_DETAILS } from 'apps/pos/src/services/mocks/fixtures/merchantDetails';
import { DashboardGraphQLMerchant } from '@libs/shared-types';
import { getMockModularResponseWithDeviceStep } from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/DeviceOrdering/DevicePaymentMethods/__tests__/mocks/fixtures';


export const getMockUseOnboardingContext = () => ({
  values: {
    isNewOnboarding: false,
    merchantId: 'PhjLpFaE7tz4cA',
    onboardingSteps: [],
  },
  states: {
    merchantDetails: MOCK_MERCHANT_DETAILS as unknown as DashboardGraphQLMerchant,
    handleModularUpdate: jest.fn(),
    handleGoToNextStep: jest.fn(),
    isUpdateModularLoading: false,
    isPosEkycAgent: false,
    modularConfig: getMockModularResponseWithDeviceStep({}).merchantModularOnboardingDetailsUpdateAsSales,
  },
  handlers: {
    getComponentConfigFromStep: () => ({ title: 'Device delivery address' }),
    getOnboardingProgress: () => ({ totalSteps: 6, totalCompletedSteps: 2 }),
    getStepConfigStepSlug: () => ({ modularKey: 'device_delivery_address_component' }),
    updateModularConfig: jest.fn(),
    handleProceedToNextComponent: () => ({}),
  },
});
