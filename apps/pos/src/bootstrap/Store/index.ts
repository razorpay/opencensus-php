import create from 'zustand';
import { OnboardingStoreState } from 'apps/pos/src/app/types/SalesAssistedOnboarding';

// Create the Zustand store with types
const useOnboardingStore = create<OnboardingStoreState>(() => ({}));

export default useOnboardingStore;
