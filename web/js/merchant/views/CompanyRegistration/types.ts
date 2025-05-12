import type { IconComponent, IconColors } from '@razorpay/blade/components';
import type { commonColor } from './components/CompanyRegisterBanner';
import { AccountType } from './components/MultiAccountBodyFooter';
import { POST_SALESFORCE_STEPS_STATUS, RIZE_JOURNEY } from './constant';

export interface ApiResponse<T> {
  status_code: number;
  success: boolean;
  data?: T;
  errors?: string[];
}

export type ModularOnboardingMilestone = {
  name: string;
  status: string;
  can_submit: boolean;
  steps: [ModularOnboardingStep];
  progress: number;
  meta: { template: string; title: string };
};
export type ModularOnboardingStep = {
  name: string;
  progress: number;
  status: string;
  components: [ModularOnboardingStepComponent];
  meta: {
    icon: string;
    template: string;
    title: string;
    description: string;
  };
};

export type ModularOnboardingStepComponent = {
  name: string;
  progress: number;
  status: string;
  verification: string;
  meta: any;
  is_required: boolean;
  fields: any[];
};

export type WorkflowData = {
  id: string;
  milestones: [ModularOnboardingMilestone];
  progress: number;
  status: string;
};
export type WorkflowConfig = {
  success: boolean;
  workflow_data: WorkflowData;
  onboarding_state: {
    components: string[];
    milestones: string[];
    steps: string[];
  };
  onboarding_status: string;
  country_code: string;
  onboarding_type: string;
  merchant_type: string;
};
export type IconWithTextUiT = {
  Icon: IconComponent;
  iconColor: IconColors;
  textColor: typeof commonColor | 'surface.text.gray.subtle';
  LoopOverData: Array<string>;
};
export type IconWithTextWrapperT = IconWithTextUiT & {
  isSmallDevice: boolean;
};
export type BannerDataT = {
  firstLine: string;
  secondLineSubText: string;
  highlightedText: string;
  isIconContent: boolean;
  text: string | null;
  isButtonRequire: boolean;
  buttonText: string;
};
export type HeaderSectionT = {
  bannerData: BannerDataT;
  isSmallDevice: boolean;
  screen: RizeJourneyType;
};
export type BannerData = {
  main: {
    firstLine: string;
    secondLine: {
      subText: string;
      highlightedText: string;
    };
  };
  midSection?: {
    isIconContent: boolean;
    text?: string;
  };
  button: {
    isButtonRequire: boolean;
    buttonText?: string;
  };
};
export type RizeJourneyType = (typeof RIZE_JOURNEY)[keyof typeof RIZE_JOURNEY];

export type SfStatusType = (typeof POST_SALESFORCE_STEPS_STATUS)[keyof typeof POST_SALESFORCE_STEPS_STATUS];
export type AccountT = (typeof AccountType)[keyof typeof AccountType];

export type StatusStepsType = {
  icon: IconComponent;
  title: string;
  status: SfStatusType;
};