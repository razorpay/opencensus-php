import { BANNER_DATA, BANNER_JSON_KEYS } from './constant';
import { useTheme } from '@razorpay/blade/components';
import { useBreakpoint } from '@razorpay/blade/utils';
import Ashoka from 'assets/rize/rize_incorporation/ashoka.svg';
import type { BannerDataT } from './types';

export const parseBannerData = ({ screenNumber = 0 }: { screenNumber: number }): BannerDataT => {
  // always map key name in below array with key in BANNER_DATA import.
  const SCREEN_KEYS = [
    BANNER_JSON_KEYS.INITIAL,
    BANNER_JSON_KEYS.RESUME,
    BANNER_JSON_KEYS.PROGRESS,
    BANNER_JSON_KEYS.CONGRATULATION,
  ] as const;

  const { main, midSection, button } = BANNER_DATA[SCREEN_KEYS[screenNumber]];

  return {
    firstLine: main.firstLine,
    secondLineSubText: main.secondLine.subText,
    highlightedText: main.secondLine.highlightedText,
    isIconContent: midSection?.isIconContent ?? false,
    text: midSection?.text || null,
    isButtonRequire: button?.isButtonRequire ?? false,
    buttonText: button?.buttonText || '',
  };
};

export const styleBasedOnDevice = (isMobile) => {
  const commonStyle = {
    display: 'flex',
    flexDirection: 'column',
    alignItems: 'flex-start',
    justifyContent: 'space-between',
    backgroundColor: 'surface.background.primary.intense',
  };
  if (isMobile) {
    return {
      ...commonStyle,
      height: '352px',
      padding: 'spacing.7',
    };
  }
  return {
    ...commonStyle,
    height: '232px',
    borderRadius: 'large',
    backgroundImage: `url(${Ashoka})`,
    backgroundRepeat: 'no-repeat',
    backgroundSize: 'contain',
    backgroundOrigin: 'border-box',
    backgroundPosition: 'right',
    marginX: 'spacing.7',
    padding: 'spacing.8',
  };
};

interface UseScreen {
  isDesktop: boolean;
  isMobile: boolean;
  isTablet: boolean;
}
/**
 * Support for Mobile, Tablet & Desktop based on breakpoint supported by Blade
 */
export const useScreen = (): UseScreen => {
  const { theme } = useTheme();
  const { matchedBreakpoint } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });

  return {
    isDesktop: matchedBreakpoint === 'l' || matchedBreakpoint === 'xl',
    isMobile:
      matchedBreakpoint === 's' || matchedBreakpoint === 'xs' || matchedBreakpoint === 'base',
    isTablet: matchedBreakpoint === 'm',
  };
};
