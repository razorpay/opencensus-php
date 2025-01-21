import create from 'zustand';

import { OnboardingStatusType } from '@apps/digital-bills/src/bootstrap/Hoc/WithOnboardingRedirect/types';

type AppState = {
  onboardingStatus: OnboardingStatusType | null;
  updateOnboardingStatus: (status: OnboardingStatusType) => void;
};

const useAppStore = create<AppState>((set) => ({
  onboardingStatus: null,
  updateOnboardingStatus: (status: OnboardingStatusType) =>
    set((state) => ({ ...state, onboardingStatus: status })),
}));

export default useAppStore;
