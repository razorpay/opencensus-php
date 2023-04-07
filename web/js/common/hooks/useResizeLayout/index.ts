import { useEffect, useState } from 'react';
const getScreenBreakpoint = (width): boolean => window.innerWidth <= width;

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
