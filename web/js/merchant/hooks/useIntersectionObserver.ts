import { RefObject, useEffect, useRef, useState } from 'react';

// ref: https://stackoverflow.com/a/67826055/6242649
export const useIntersectionObserver = (ref: RefObject<HTMLElement>): boolean => {
  const observerRef = useRef<IntersectionObserver | null>(null);
  const [isOnScreen, setIsOnScreen] = useState(false);
  useEffect(() => {
    observerRef.current = new IntersectionObserver(([entry]) =>
      setIsOnScreen(entry.isIntersecting),
    );
  }, []);
  useEffect(() => {
    if (ref.current) observerRef.current?.observe(ref.current);
    return () => {
      observerRef.current?.disconnect();
    };
  }, [ref]);
  return isOnScreen;
};
