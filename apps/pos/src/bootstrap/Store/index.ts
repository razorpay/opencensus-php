import create from 'zustand';
import {
  OnboardingStoreState,
  OnboardingWorkflowProduct,
  STATUS_FILTERS,
} from 'apps/pos/src/app/types/SalesAssistedOnboarding';
import { devtools } from 'zustand/middleware';
import { BeforeInstallPromptEvent } from '../../app/views/PosEkyc/types';
import moment from 'moment';

const useOnboardingStore = create<OnboardingStoreState>(
  devtools((set) => ({
    workflowProduct: OnboardingWorkflowProduct.ASSISTED_ONBOARDING,
    isPosEkycAgent: false,
    pwaPrompt: null,
    filters: {
      dateRange: {
        startDate: moment().subtract(10, 'days').startOf('day').unix(),
        endDate: moment().endOf('day').unix(),
      },
      activationStatus: 'all',
    },
    setWorkflowProduct: (workflowProduct: OnboardingWorkflowProduct) => set({ workflowProduct }),
    setIsPosEkycAgent: (isPosEkycAgent: boolean) => set({ isPosEkycAgent }),
    setPwaPrompt: (pwaPrompt: BeforeInstallPromptEvent | null) => set({ pwaPrompt }),
    setFilters: (filters: {
      dateRange: { startDate: number; endDate: number };
      activationStatus: STATUS_FILTERS;
    }) => set({ filters }),
  })),
);

export default useOnboardingStore;
