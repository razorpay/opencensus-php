
import { useTheme, Theme } from '@razorpay/blade/components';
import { useBreakpoint } from '@razorpay/blade/utils';

const defaultMobileBreakpoints: Readonly<Array<keyof Theme['breakpoints']>> = ['base', 'xs', 's'];

/**
 * Custom hook to detect if the current device is mobile based on breakpoints.
 * 
 * @param {Readonly<Array<keyof Theme['breakpoints']>>} mobileBreakpoints - Array of breakpoint keys to consider as mobile.
 * @returns {boolean} - Returns true if the device is mobile based on matched breakpoints or device type.
 * 
 * @example
 * const isMobile = useMobile();
 * if (isMobile) {
 *   console.log('This is a mobile device');
 * }
 */
export const useMobile = (
  mobileBreakpoints: Readonly<Array<keyof Theme['breakpoints']>> = defaultMobileBreakpoints,
): boolean => {
  const { theme } = useTheme();
  const { matchedBreakpoint, matchedDeviceType } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });

  return (
    matchedDeviceType === 'mobile' ||
    (!!matchedBreakpoint && mobileBreakpoints.includes(matchedBreakpoint))
  );
};
