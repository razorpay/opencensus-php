import { useRef, useEffect } from 'react';
import { debounce } from '../utils';

type FunctionType = (...args: any[]) => any;

/**
 * Debounce hook
 * @param {FunctionType} callback - The callback to debounce
 * @param {number} wait - The duration to debounce
 * @returns {FunctionType} - The debounced callback
 *
 * @example
 * const debouncedFunction = useDebounce((val) => console.log(val), 300);
 * debouncedFunction('test');
 */
export const useDebounce = (callback: FunctionType, wait: number): FunctionType => {
  function createDebouncedCallback(fn: FunctionType): FunctionType {
    return debounce(fn, wait);
  }

  const callbackRef = useRef<FunctionType>(callback);
  const debouncedCallbackRef = useRef<FunctionType>(createDebouncedCallback(callback));

  useEffect(() => {
    callbackRef.current = callback;
  });

  useEffect(() => {
    debouncedCallbackRef.current = createDebouncedCallback((...args) => {
      callbackRef.current(...args);
    });
  }, [wait]);

  function debouncedCallbackWithEventPersist(...args: any[]) {
    args?.forEach((arg) => {
      if (!(arg instanceof Event) && arg?.nativeEvent instanceof Event) {
        // Synthetic events need to be persisted
        arg.persist?.();
      }
    });
    return debouncedCallbackRef.current(...args);
  }

  return debouncedCallbackWithEventPersist;
};
