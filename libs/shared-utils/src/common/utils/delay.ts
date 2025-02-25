/**
 * Delays the execution for a specified amount of time.
 * 
 * @param {number} [time=1000] - The time in milliseconds to delay (default is 1000 ms).
 * @returns {Promise<void>} - A promise that resolves after the delay.
 * 
 * @example
 * delay(2000).then(() => {
 *   console.log('Executed after 2 seconds');
 * });
 */
export const delay = (time: number = 1000): Promise<void> => 
  new Promise((resolve) => setTimeout(resolve, time));
