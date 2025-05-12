import { useTheme } from '@razorpay/blade/components';
import { useBreakpoint } from '@razorpay/blade/utils';
import { renderHook } from '@testing-library/react-hooks';

import { BANNER_DATA } from '../constant';
import { useScreen, parseBannerData } from '../utils';

jest.mock('@razorpay/blade/components', () => ({
  useTheme: jest.fn(),
}));

jest.mock('@razorpay/blade/utils', () => ({
  useBreakpoint: jest.fn(),
}));
describe('Test useScreen utils function', () => {
  beforeEach(() => {
    (useTheme as jest.Mock).mockReturnValue({
      theme: { breakpoints: { xs: 0, s: 480, m: 768, l: 1024, xl: 1280 } },
    });
  });

  test('should return isDesktop as true for large screens', () => {
    (useBreakpoint as jest.Mock).mockReturnValue({ matchedBreakpoint: 'l' });

    const { result } = renderHook(() => useScreen());

    expect(result.current.isDesktop).toBe(true);
    expect(result.current.isMobile).toBe(false);
    expect(result.current.isTablet).toBe(false);
  });
  test('should return isDesktop as true for xlarge screens', () => {
    (useBreakpoint as jest.Mock).mockReturnValue({ matchedBreakpoint: 'xl' });

    const { result } = renderHook(() => useScreen());

    expect(result.current.isDesktop).toBe(true);
    expect(result.current.isMobile).toBe(false);
    expect(result.current.isTablet).toBe(false);
  });
  test('should return isMobile as true for s screens', () => {
    (useBreakpoint as jest.Mock).mockReturnValue({ matchedBreakpoint: 's' });

    const { result } = renderHook(() => useScreen());

    expect(result.current.isDesktop).toBe(false);
    expect(result.current.isMobile).toBe(true);
    expect(result.current.isTablet).toBe(false);
  });
  test('should return isMobile as true for base screens', () => {
    (useBreakpoint as jest.Mock).mockReturnValue({ matchedBreakpoint: 'base' });

    const { result } = renderHook(() => useScreen());

    expect(result.current.isDesktop).toBe(false);
    expect(result.current.isMobile).toBe(true);
    expect(result.current.isTablet).toBe(false);
  });
  test('should return isTablet as true for m screens', () => {
    (useBreakpoint as jest.Mock).mockReturnValue({ matchedBreakpoint: 'm' });

    const { result } = renderHook(() => useScreen());

    expect(result.current.isDesktop).toBe(false);
    expect(result.current.isMobile).toBe(false);
    expect(result.current.isTablet).toBe(true);
  });
});

describe('Test parseBannerData utils function', () => {
  test('should return banner data on passing screen is set as `initial_screen`', () => {
    const result = parseBannerData({ screen: 'initial_screen' });
    expect(result.firstLine).toBe(BANNER_DATA.initial_screen.main.firstLine);
    expect(result.secondLineSubText).toBe(BANNER_DATA.initial_screen.main.secondLine.subText);
    expect(result.highlightedText).toBe(BANNER_DATA.initial_screen.main.secondLine.highlightedText);
    expect(result.isIconContent).toBe(BANNER_DATA.initial_screen.midSection?.isIconContent);
    expect(result.text).toBeFalsy();
    expect(result.isButtonRequire).toBe(BANNER_DATA.initial_screen.button.isButtonRequire);
    expect(result.buttonText).toBe(BANNER_DATA.initial_screen.button.buttonText);
  });
});
