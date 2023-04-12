import { useRef, useEffect } from 'react';
import debounce from 'common/utils/debounce';

type FunctionType = (...args) => any;
/**
 * Debounce hook
 * @param {function} callback The callback to debounce
 * @param {number} wait The duration to debounce
 * @returns {function} The debounced callback
 */
function useDebounce(callback: FunctionType, wait: number): FunctionType {
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

  function debouncedCallbackWithEventPersist(...args) {
    args?.forEach((arg) => {
      if (!(arg instanceof Event) && arg.nativeEvent instanceof Event) {
        // Synthetic events need to be persisted
        arg.persist();
      }
    });
    return debouncedCallbackRef.current(...args);
  }
  return debouncedCallbackWithEventPersist;
}

export default useDebounce;
