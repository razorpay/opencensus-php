/**
 * Mutates the array by moving an element from one index to another.
 * This is a helper function used within `arrayMove`.
 *
 * @template T - The type of elements in the array.
 * @param {T[]} array - The array to mutate.
 * @param {number} from - The index of the element to move.
 * @param {number} to - The index where the element should be moved to.
 */
const _arrayMoveMutate = <T>(array: T[], from: number, to: number): void => {
  array.splice(to < 0 ? array.length + to : to, 0, array.splice(from, 1)[0]);
};

/**
 * Moves an element from one index to another in a new array without mutating the original array.
 *
 * @template T - The type of elements in the array.
 * @param {T[]} array - The original array.
 * @param {number} from - The index of the element to move.
 * @param {number} to - The index where the element should be moved to.
 * @returns {T[]} - A new array with the element moved to the new position.
 *
 * @example
 * const arr = [1, 2, 3, 4];
 * const result = arrayMove(arr, 1, 3);
 * console.log(result);
 * // Output: [1, 3, 4, 2]
 */
export const arrayMove = <T>(array: T[], from: number, to: number): T[] => {
  const newArray = array.slice(); // Create a copy of the array
  _arrayMoveMutate(newArray, from, to); // Mutate the copied array
  return newArray; // Return the new array with the element moved
};
