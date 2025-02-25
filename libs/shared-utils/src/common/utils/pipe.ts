/**
 * Composes multiple functions into a single function. The output of each function is passed as input to the next.
 * The first function in the chain is called with the initial arguments, and subsequent functions receive the result
 * of the previous function.
 *
 * @example
 * const add = (x: number) => x + 1;
 * const double = (x: number) => x * 2;
 * const result = pipe(add, double)(2);
 * console.log(result); // Output: 6
 *
 * @param {...Function[]} funcs - A series of functions to be executed in sequence.
 * @returns {Function} - A function that takes initial arguments and applies the piped functions in sequence.
 */
export const pipe = <T>(...funcs: ((arg: T) => T)[]): ((...args: T[]) => T) => {
  const first = funcs.shift(); // Remove the first function from the array
  return (...args: T[]) => {
    return funcs.reduce((returnVal, currentFn) => {
      return currentFn(returnVal); // Apply each function in the chain
      // @ts-ignore
    }, first!(...args)); // Start with the first function
  };
};
