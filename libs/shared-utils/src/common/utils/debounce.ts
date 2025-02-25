/**
 * Creates a debounced version of a function that delays its execution
 * until after a specified delay has passed since the last time it was invoked.
 *
 * @param {Function} fn - The function to debounce.
 * @param {number} [delay=0] - The number of milliseconds to delay the function execution.
 * @returns {Function} The debounced function with a `cancel` method to clear the timeout.
 *
 * @example
 * const debouncedLog = debounce(() => console.log('Hello!'), 500);
 * debouncedLog(); // Will log 'Hello!' after 500ms if not invoked again.
 * debouncedLog.cancel(); // Cancels the pending execution.
 */
export const debounce = (fn: (...args: any[]) => void, delay: number = 0): any => {
  let timer: number | null = null;

  const debouncedFunction = function (...args: any[]) {
    /**
     * Cancels the timeout if it exists.
     */
    function cancel() {
      if (timer !== null) {
        window.clearTimeout(timer);
      }
    }
    
    cancel();
    // Binds the function call with the current `this` context and arguments
    // @ts-ignore
    timer = window.setTimeout(fn.bind(this, ...args), delay);

    debouncedFunction.cancel = cancel;
  };

  debouncedFunction.cancel = () => {
    if (timer !== null) {
      window.clearTimeout(timer);
    }
  };

  return debouncedFunction;
};

