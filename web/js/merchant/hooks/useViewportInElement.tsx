import { RefObject, useEffect, useRef, useState } from 'react';

/**
 * Tracks whether the viewport is completely inside the given element
 * @param ref Ref of element to track
 * @returns boolean indicating whether the viewport is completely inside the given element
 */
const useViewportInElement = <T extends HTMLElement>(ref: RefObject<T>): boolean => {
  const topObserverRef = useRef<IntersectionObserver | null>(null);
  const bottomObserverRef = useRef<IntersectionObserver | null>(null);
  const [isTopOnScreen, setIsTopOnScreen] = useState(false);
  const [isBottomOnScreen, setIsBottomOnScreen] = useState(false);

  useEffect(() => {
    topObserverRef.current = new IntersectionObserver(
      ([entry]): void => setIsTopOnScreen(entry.isIntersecting),
      {
        rootMargin: '0% 0% -100% 0%',
      },
    );

    bottomObserverRef.current = new IntersectionObserver(
      ([entry]): void => setIsBottomOnScreen(entry.isIntersecting),
      {
        rootMargin: '-100% 0% 100% 0%',
      },
    );
  }, []);

  useEffect(() => {
    if (ref.current) {
      topObserverRef.current?.observe(ref.current);
      bottomObserverRef.current?.observe(ref.current);
    }

    return () => {
      topObserverRef.current?.disconnect();
      bottomObserverRef.current?.disconnect();
    };
  }, [ref]);

  return isTopOnScreen && isBottomOnScreen;
};

export default useViewportInElement;
