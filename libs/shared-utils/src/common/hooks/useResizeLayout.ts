import { useEffect, useState } from 'react';
/**
 * Checks if the current screen width is under a given breakpoint.
 * 
 * @param {number} width - The width to compare with the current window inner width.
 * @returns {boolean} - Returns true if the screen width is under the given breakpoint.
 */
const getScreenBreakpoint = (width: number): boolean => window.innerWidth <= width;

/**
 * Custom hook to determine if the screen width is below a specified innerWidth (default is 768).
 * 
 * @param {Object} param - An object containing the innerWidth breakpoint.
 * @param {number} param.innerWidth - The inner width breakpoint for the layout (default: 768).
 * @returns {boolean} - Returns true if the screen width is below the specified innerWidth.
 * 
 * @example
 * const isMobileLayout = useResizeLayout({ innerWidth: 768 });
 * if (isMobileLayout) {
 *   console.log('Mobile layout is active');
 * }
 */
export const useResizeLayout = ({ innerWidth = 768 }: { innerWidth: number }): boolean => {
  const [isScreenUnderBreakpoint, setIsScreenUnderBreakpoint] = useState(
    getScreenBreakpoint(innerWidth),
  );

  useEffect((): (() => void) => {
    const onResize = (): void => {
      setIsScreenUnderBreakpoint(getScreenBreakpoint(innerWidth));
    };
    window.addEventListener('resize', onResize);
    return (): void => {
      window.removeEventListener('resize', onResize);
    };
  }, [innerWidth]);

  return isScreenUnderBreakpoint;
};
