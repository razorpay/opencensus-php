import { useTheme, useBreakpoint } from '@razorpay/blade/utils';
interface UseScreen {
  isMobile: boolean;
}

export const useScreen = (): UseScreen => {
  const { theme } = useTheme();
  const { matchedDeviceType } = useBreakpoint({ breakpoints: theme.breakpoints });

  return {
    isMobile: matchedDeviceType === 'mobile',
  };
};
