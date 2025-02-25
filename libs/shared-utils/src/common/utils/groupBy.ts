/**
 * Groups an array of records by the specified column name.
 * 
 * @param {T[]} records - The array of records to group.
 * @param {keyof T} colName - The column name by which to group the records.
 * @returns {Record<string, T[]>} - An object where the keys are unique values from the specified column, and the values are arrays of records that share that column value.
 * 
 * @example
 * const records = [
 *   { id: 1, category: 'fruit', name: 'apple' },
 *   { id: 2, category: 'vegetable', name: 'carrot' },
 *   { id: 3, category: 'fruit', name: 'banana' },
 * ];
 * const grouped = groupBy(records, 'category');
 * console.log(grouped);
 * // Output:
 * // {
 * //   fruit: [
 * //     { id: 1, category: 'fruit', name: 'apple' },
 * //     { id: 3, category: 'fruit', name: 'banana' }
 * //   ],
 * //   vegetable: [
 * //     { id: 2, category: 'vegetable', name: 'carrot' }
 * //   ]
 * // }
 */
export const groupBy = <T>(records: T[], colName: keyof T): Record<string, T[]> => {
  const result: Record<string, T[]> = {};

  records.forEach((record) => {
    if (!Object.prototype.hasOwnProperty.call(record, colName)) {
      return;
    }

    const colValue = String(record[colName]); // Ensure that the key is a string
    const colRecords = (result[colValue] = result[colValue] || []);

    colRecords.push(record);
  });

  return result;
};
