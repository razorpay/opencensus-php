import create from 'zustand';
import {
  OnboardingStoreState,
  OnboardingWorkflowProduct,
} from 'apps/pos/src/app/types/SalesAssistedOnboarding';
import { devtools } from 'zustand/middleware';

const useOnboardingStore = create<OnboardingStoreState>(
  devtools((set) => ({
    workflowProduct: OnboardingWorkflowProduct.ASSISTED_ONBOARDING,
    isPosEkycAgent: false,
    setWorkflowProduct: (workflowProduct: OnboardingWorkflowProduct) => set({ workflowProduct }),
    setIsPosEkycAgent: (isPosEkycAgent: boolean) => set({ isPosEkycAgent }),
  })),
);

export default useOnboardingStore;
