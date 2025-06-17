import { useTheme } from '@razorpay/blade/components';
import { useBreakpoint } from '@razorpay/blade/utils';
type breakpoints = string | undefined;
type useBladeBreakpointsTypes = {
  matchedBreakpoint: breakpoints;
  isMobile: boolean;
  isDesktop: boolean;
  isLargeScreen: boolean; //desktop and tablets
};

export const useBladeBreakpoints = (): useBladeBreakpointsTypes => {
  const { theme } = useTheme();
  const { matchedBreakpoint, matchedDeviceType } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });

  return {
    matchedBreakpoint,
    isMobile: matchedDeviceType === 'mobile',
    isDesktop: matchedDeviceType === 'desktop',
    isLargeScreen: matchedBreakpoint === 'l' || matchedBreakpoint === 'xl',
  };
};
