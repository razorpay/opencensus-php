import { useLayoutEffect } from 'react';

const BodyScrollLock = (): null => {
  useLayoutEffect(() => {
    const { width, overflow } = window.getComputedStyle(document.body);
    document.body.style.width = width;
    document.body.style.overflow = 'hidden';
    return () => {
      document.body.style.width = width;
      document.body.style.overflow = overflow;
    };
  }, []);
  return null;
};

export default BodyScrollLock;
