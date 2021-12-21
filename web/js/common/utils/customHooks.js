import { useRef, useEffect } from 'react';

/* Can Pass Multiple Elements Ref to this to make sure you cliked outSide all of them */
export const useClickOutSide = (elemRefs, callback) => {
  // we are using the pattern of refering callback to make sure to track the latest callback if element changed
  const callbackRef = useRef();
  callbackRef.current = callback;
  useEffect(() => {
    const handleClickOutSide = (event) => {
      let isOutSide = true;
      if (!elemRefs || (elemRefs && !Array.isArray(elemRefs))) return;
      // looking through each of the elem to make sure we didn't clicked inside of any of it
      elemRefs.forEach((item) => {
        if (item?.current?.contains(event.target)) isOutSide = false;
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
