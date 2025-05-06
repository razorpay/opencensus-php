import { useState, useRef, useEffect } from 'react';

interface UseHorizontalScrollProps {
  /**
   * Callback function triggered on scroll.
   * Provides the current scroll position, total scroll width, and visible width.
   */
  onScroll?: (scrollLeft: number, scrollWidth: number, clientWidth: number) => void;
}

const useHorizontalScroll = ({ onScroll }: UseHorizontalScrollProps = {}) => {
  const scrollContainerRef = useRef<HTMLDivElement | null>(null);
  const [isScrolledLeft, setIsScrolledLeft] = useState(false);
  const [isScrolledRight, setIsScrolledRight] = useState(false);

  const checkScrollPosition = (): void => {
    if (!scrollContainerRef.current) return;

    const { scrollLeft, scrollWidth, clientWidth } = scrollContainerRef.current;

    setIsScrolledLeft(scrollLeft > 0);
    setIsScrolledRight(scrollLeft < scrollWidth - clientWidth);

    if (onScroll) {
      onScroll(scrollLeft, scrollWidth, clientWidth);
    }
  };

  useEffect(() => {
    const container = scrollContainerRef.current;

    if (!container) return; // Ensure ref exists before proceeding

    requestAnimationFrame(checkScrollPosition);

    container.addEventListener('scroll', checkScrollPosition);
    window.addEventListener('resize', checkScrollPosition);

    return () => {
      container.removeEventListener('scroll', checkScrollPosition);
      window.removeEventListener('resize', checkScrollPosition);
    };
  }, [scrollContainerRef.current]);

  return { scrollContainerRef, isScrolledLeft, isScrolledRight };
};

export default useHorizontalScroll;
