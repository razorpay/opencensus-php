
import { useTheme, Theme } from '@razorpay/blade/components';
import { useBreakpoint } from '@razorpay/blade/utils';

const defaultMobileBreakoints: Readonly<Array<keyof Theme['breakpoints']>> = ['base', 'xs', 's'];

export const useMobile = (
  mobileBreakoints: Readonly<Array<keyof Theme['breakpoints']>> = defaultMobileBreakoints,
): boolean => {
  const { theme } = useTheme();
  const { matchedBreakpoint, matchedDeviceType } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });

  return (
    matchedDeviceType === 'mobile' ||
    (!!matchedBreakpoint && mobileBreakoints.includes(matchedBreakpoint))
  );
};
