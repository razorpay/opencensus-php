/**
 * Converts an array of objects to CSV format. Note that this only works for
 * one-level nested JSON objects.
 *
 * @template T - The type of the object in the array.
 * @param {T[]} arr - The array of objects to be converted to CSV.
 * @returns {string} - The CSV formatted string.
 *
 * @example
 * const data = [
 *   {
 *     id: "FBXzLRJYkqA237",
 *     amount: 1200,
 *     status: "PENDING",
 *     created_at: "2020-07-07 14:25:37.11105 +0530 IST m=+37.486976552",
 *     interest_repaid: 0,
 *     principal_repaid: 500
 *   },
 *   {
 *     id: "ABCzLRJYkqA237",
 *     amount: 1100,
 *     status: "PENDING",
 *     created_at: "2020-07-07 14:25:37.11105 +0530 IST m=+37.486976552",
 *     interest_repaid: 0,
 *     principal_repaid: 1000
 *   }
 * ];
 * 
 * const csv = arrayObjToCsv(data);
 * console.log(csv);
 * // Output:
 * // id,amount,status,created_at,interest_repaid,principal_repaid
 * // FBXzLRJYkqA237,1200,PENDING,2020-07-07 14:25:37.11105 +0530 IST m=+37.486976552,0,500
 * // ABCzLRJYkqA237,1100,PENDING,2020-07-07 14:25:37.11105 +0530 IST m=+37.486976552,0,1000
 */
export const arrayObjToCsv = <T extends object>(arr: T[]): string => {
  const array = [Object.keys(arr[0])].concat(arr as unknown as string[][]);

  return array
    .map((it) => {
      return Object.values(it).toString();
    })
    .join('\n');
};
