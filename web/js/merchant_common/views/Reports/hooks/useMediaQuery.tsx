import { useState, useEffect } from 'react';
import debounce from 'common/utils/debounce';

export const useMediaQuery = (
  breakpoint: string,
): {
  breakpointMatched: boolean;
} => {
  const [isBreakpointMatched, setBreakpointMatched] = useState(
    Boolean(window?.matchMedia(breakpoint)?.matches),
  );

  useEffect(() => {
    // initial update of state
    setBreakpointMatched(window?.matchMedia(breakpoint).matches);
  }, []);

  useEffect(() => {
    const handleMatch = debounce((e) => setBreakpointMatched(e.matches), 200);

    const mediaQuery = window?.matchMedia(breakpoint);
    mediaQuery?.addEventListener('change', handleMatch);
    return () => mediaQuery?.removeEventListener('change', handleMatch);
  }, [breakpoint]);

  return { breakpointMatched: isBreakpointMatched };
};
