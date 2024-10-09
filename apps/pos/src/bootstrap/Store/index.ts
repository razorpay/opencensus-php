import create from 'zustand';
import { OnboardingStoreState } from 'apps/pos/src/app/types/SalesAssistedOnboarding';

const useOnboardingStore = create<OnboardingStoreState>((set) => ({
  workflowProduct: '',
  isPosEkycAgent: false,
  setWorkflowProduct: (workflowProduct: string) => set({ workflowProduct }),
  setIsPosEkycAgent: (isPosEkycAgent: boolean) => set({ isPosEkycAgent }),
}));

export default useOnboardingStore;
