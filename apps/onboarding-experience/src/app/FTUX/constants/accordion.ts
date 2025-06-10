import { AvailablePlatformTypesEnum } from '@OnboardingExperienceCommons/utils/website';
import active1Icon from '@OnboardingExperienceAssets/AccordionIcons/Active1.svg';
import active2Icon from '@OnboardingExperienceAssets/AccordionIcons/Active2.svg';
import active3Icon from '@OnboardingExperienceAssets/AccordionIcons/Active3.svg';
import upcoming2Icon from '@OnboardingExperienceAssets/AccordionIcons/Upcoming2.svg';
import upcoming3Icon from '@OnboardingExperienceAssets/AccordionIcons/Upcoming3.svg';

export const INTEGRATION_GUIDE: Record<string, string> = {
  [AvailablePlatformTypesEnum.WEBSITE]:
    'https://razorpay.com/docs/payments/payment-gateway/web-integration/standard/build-integration/',
  [AvailablePlatformTypesEnum.IOS]:
    'https://razorpay.com/docs/payments/payment-gateway/react-native-integration/standard/build-integration-ios/',
  [AvailablePlatformTypesEnum.ANDROID]:
    'https://razorpay.com/docs/payments/payment-gateway/react-native-integration/standard/build-integration-android/',
};

export const INVITE_TEAM_MEMBER_VIDEO_URL = 'https://www.youtube.com/watch?v=SFHbcs-lSio';

export const PRICING_URL = 'https://razorpay.com/pricing/';

// Arrays of icons for each state (Currently max supported 3 steps)
export const activeStepIcons = [active1Icon, active2Icon, active3Icon];
export const upcomingStepIcons = [active1Icon, upcoming2Icon, upcoming3Icon]; // Use active1Icon for first step if it's upcoming
