export type OnboardingStatus =
  | null
  | 'interested'
  | 'completed'
  | 'in-progress'
  | 'pending'
  | 'activated'
  | 'deactivated';

export type SetOnboardingStatus = (value: OnboardingStatus) => void;

export type OnboardingStatusHistory = {
  status: string;
  timestamp: number;
};

export type OnboardingCategory = 'rtb' | 'non-rtb';

export type FetchOnboardingResponse = {
  data: {
    id: string;
    merchant_id: string;
    onboarding_status: OnboardingStatus;
    onboarding_status_history: OnboardingStatusHistory[];
    created_at: number;
    updated_at: number;
    eligible: boolean;
    pricing: number;
    category: OnboardingCategory;
    reason?: 'tpv_enabled' | 'contact_optional' | 'cfb_merchant' | 'razorpay_wallet_enabled';
  };
  status_code: number;
  success: boolean;
};
