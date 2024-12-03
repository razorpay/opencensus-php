import create from 'zustand';
import {
  OnboardingStoreState,
  OnboardingWorkflowProduct,
} from 'apps/pos/src/app/types/SalesAssistedOnboarding';
import { devtools } from 'zustand/middleware';
import { BeforeInstallPromptEvent } from '../../app/views/PosEkyc/types';

const useOnboardingStore = create<OnboardingStoreState>(
  devtools((set) => ({
    workflowProduct: OnboardingWorkflowProduct.ASSISTED_ONBOARDING,
    isPosEkycAgent: false,
    pwaPrompt: null,
    setWorkflowProduct: (workflowProduct: OnboardingWorkflowProduct) => set({ workflowProduct }),
    setIsPosEkycAgent: (isPosEkycAgent: boolean) => set({ isPosEkycAgent }),
    setPwaPrompt: (pwaPrompt: BeforeInstallPromptEvent | null) => set({ pwaPrompt }),
  })),
);

export default useOnboardingStore;
