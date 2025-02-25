/**
 * Returns the difference between two arrays, meaning the elements present in the first array
 * that are not present in the second array.
 *
 * @template T - The type of elements in the arrays.
 * @param {T[]} arr1 - The first array.
 * @param {T[]} arr2 - The second array.
 * @returns {T[]} - A new array containing the elements found in the first array but not in the second.
 *
 * @example
 * const array1 = [1, 2, 3, 4];
 * const array2 = [3, 4, 5];
 * const result = arrayDiff(array1, array2);
 * console.log(result); 
 * // Output: [1, 2]
 *
 * @example
 * const array1 = ['apple', 'banana', 'orange'];
 * const array2 = ['banana', 'grape'];
 * const result = arrayDiff(array1, array2);
 * console.log(result);
 * // Output: ['apple', 'orange']
 */
export function arrayDiff<T>(arr1: T[], arr2: T[]): T[] {
  if (arr1.length < arr2.length) {
    let tempArr = arr1;
    arr1 = arr2;
    arr2 = tempArr;
  }

  return arr1.reduce<T[]>((prev, curr) => {
    if (arr2.indexOf(curr) === -1) {
      prev.push(curr);
    }
    return prev;
  }, []);
}
