import { useEffect, useRef, useState } from 'react';
import { BoxRefType } from '@razorpay/blade/components';

interface UseIntersectionObserver {
  callback: () => void;
  options?: IntersectionObserverInit;
}

const useIntersectionObserver = ({ callback, options = {} }: UseIntersectionObserver) => {
  const observerRef = useRef<IntersectionObserver | null>(null);
  const [targetRef, setTargetRef] = useState<BoxRefType | null>(null);

  useEffect(() => {
    if (!targetRef) return;

    observerRef.current = new IntersectionObserver(
      (entries) => {
        if (entries[0].isIntersecting) {
          callback();
        }
      },
      { rootMargin: '50px', threshold: 0.5, ...options },
    );

    observerRef.current.observe(targetRef);

    return () => {
      if (observerRef.current) observerRef.current.disconnect();
    };
  }, [targetRef, callback, options]);

  return setTargetRef;
};

export default useIntersectionObserver;
