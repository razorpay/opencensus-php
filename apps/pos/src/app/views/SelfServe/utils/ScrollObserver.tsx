import React from 'react';

type ScrollObserverContextValueType = {
  attachTarget: (ref: React.RefObject<HTMLElement>) => void;
  detachTarget: (ref: React.RefObject<HTMLElement>) => void;
  visibleElement: Element | undefined;
};

export const ScrollObserverContext = React.createContext<ScrollObserverContextValueType | null>(
  null,
);

/**
 * Creates IntersectionObserver instance
 */
export const ScrollObserverProvider = ({
  children,
  observerConfig = { rootMargin: undefined },
}: {
  children: React.ReactNode;
  observerConfig?: IntersectionObserverInit;
}): JSX.Element => {
  const [observer, setObserver] = React.useState<IntersectionObserver | undefined>(undefined);
  const [visibleElement, setVisibleElement] = React.useState<Element | undefined>(undefined);

  const handleIntersection: IntersectionObserverCallback = (entries) => {
    const intersectionTarget = entries.find((entry) => entry.isIntersecting === true);
    setVisibleElement(intersectionTarget?.target);
  };

  React.useEffect(() => {
    const intersectionObserver = new IntersectionObserver(handleIntersection, observerConfig);
    setObserver(intersectionObserver);
  }, []);

  const attachTarget = (ref: React.RefObject<HTMLElement>): void => {
    if (ref?.current && observer) {
      observer.observe(ref.current);
    }
  };

  const detachTarget = (ref: React.RefObject<HTMLElement>): void => {
    if (ref?.current && observer) {
      observer.unobserve(ref.current);
    }
  };

  const contextValue: ScrollObserverContextValueType = {
    attachTarget,
    detachTarget,
    visibleElement,
  };

  return (
    <ScrollObserverContext.Provider value={contextValue}>{children}</ScrollObserverContext.Provider>
  );
};
type VoidFuncType = () => void;
type ScrollObserverParamTwoType =
  | VoidFuncType
  | {
      onVisible: VoidFuncType;
      onInvisible: VoidFuncType;
    }
  | undefined;
/**
 * Listen to scroll intersection events to know if section is viewed
 *
 * ## Usage:
 * ```js
 * useScrollObserver(targetRef, () => {
 *  // on element visible
 * });
 * ```
 *
 * ### For calling everytime the element is visible
 * ```js
 * useScrollObserver(targetRef, () => {
 *  // on element visible
 * }, { once: false });
 * ```
 *
 * ### For element invisible callback
 * ```js
 * useScrollObserver(targetRef, {
 *  onVisible: () => {
 *    // on element visible
 *  },
 *  onInvisible: () => {
 *    // on element invisible
 *  }
 * );
 * ```
 *
 * ### For getting element visible boolean
 * ```js
 * const { isElementVisible } = useScrollObserver(targetRef, undefined, { once: false });
 * ```
 *
 */
export const useScrollObserver = (
  targetRef: React.RefObject<HTMLElement>,
  visibilityCallbacks: ScrollObserverParamTwoType,
  options: { once: boolean } = { once: true },
): { isElementVisible: boolean; scrollContext?: ScrollObserverContextValueType } => {
  const [isElementVisible, setIsElementVisible] = React.useState(false);
  const [isOnceViewed, setIsOnceViewed] = React.useState(false);
  let onVisible: VoidFuncType | undefined;
  let onInvisible: VoidFuncType | undefined;
  if (typeof visibilityCallbacks === 'object') {
    ({ onVisible, onInvisible } = visibilityCallbacks);
  } else {
    onVisible = visibilityCallbacks;
  }

  const onVisibleCallbackRef = React.useRef<(() => void) | undefined>(onVisible);
  const onInvisibleCallbackRef = React.useRef<(() => void) | undefined>(onInvisible);
  const scrollContext = React.useContext(ScrollObserverContext);
  if (!scrollContext) {
    throw new Error(
      '[ScrollObserver]: useScrollObserver was called outside of the ScrollObserverProvider',
    );
  }
  const { attachTarget, detachTarget, visibleElement } = scrollContext;

  React.useEffect(() => {
    attachTarget(targetRef);
    return (): void => {
      detachTarget(targetRef);
    };
  }, [attachTarget, detachTarget, targetRef]);

  // Handle element visibility
  React.useEffect(() => {
    if (visibleElement === targetRef.current) {
      setIsElementVisible(true);
      setIsOnceViewed(true);
    } else {
      setIsElementVisible(false);
    }

    if (options.once == true && isOnceViewed) {
      detachTarget(targetRef);
    }
  }, [visibleElement, detachTarget, options.once, isOnceViewed, targetRef]);

  // watch over element visiblity changes and call the user callback
  React.useEffect(() => {
    if (isElementVisible) {
      onVisibleCallbackRef.current?.();
    } else {
      onInvisibleCallbackRef.current?.();
    }
  }, [isElementVisible]);

  return {
    isElementVisible,
    scrollContext,
  };
};
