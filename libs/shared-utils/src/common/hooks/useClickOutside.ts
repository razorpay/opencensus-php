import { useRef, useEffect, RefObject } from 'react';
/**
 * Hook to detect clicks outside of multiple element refs.
 * 
 * @param {Array<RefObject<HTMLElement>>} elemRefs - Array of element refs to monitor for outside clicks.
 * @param {Function} callback - Function to call when a click outside the elements is detected.
 * 
 * @example
 * const ref1 = useRef(null);
 * const ref2 = useRef(null);
 * useClickOutSide([ref1, ref2], () => console.log('Clicked outside!'));
 */
export const useClickOutSide = (
  elemRefs: Array<RefObject<HTMLElement>>, 
  callback: (event: MouseEvent | TouchEvent) => void
): void => {
  const callbackRef = useRef<((event: MouseEvent | TouchEvent) => void) | null>(null);
  callbackRef.current = callback;

  useEffect(() => {
    const handleClickOutSide = (event: MouseEvent | TouchEvent) => {
      let isOutSide = true;
      if (!elemRefs || !Array.isArray(elemRefs)) return;

      elemRefs.forEach((item) => {
        if (item?.current?.contains(event.target as Node)) {
          isOutSide = false;
        }
      });

      if (isOutSide && typeof callbackRef.current === 'function' && callbackRef.current) {
        callbackRef.current(event);
      }
    };

    document.addEventListener('mousedown', handleClickOutSide, true);
    document.addEventListener('touchstart', handleClickOutSide, true);

    return () => {
      document.removeEventListener('mousedown', handleClickOutSide, true);
      document.removeEventListener('touchstart', handleClickOutSide, true);
    };
  }, [callbackRef, elemRefs]);
};
