export type OnboardingStatus = null | 'interested' | 'completed';

export type SetOnboardingStatus = (value: OnboardingStatus) => void;

export type OnboardingStatusHistory = {
  status: string;
  timestamp: number;
};

export type FetchOnboardingResponse = {
  data: {
    id: string;
    merchant_id: string;
    onboarding_status: string;
    onboarding_status_history: OnboardingStatusHistory[];
    created_at: number;
    updated_at: number;
  };
  status_code: number;
  success: boolean;
};
