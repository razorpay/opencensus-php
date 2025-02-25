/**
 * Creates a sorter function that sorts items based on the specified order.
 * 
 * @example
 * const order = ['high', 'medium', 'low'];
 * const items = [{ priority: 'low' }, { priority: 'high' }, { priority: 'medium' }];
 * const sorter = getArraySorterFromArray(order, (item) => item.priority);
 * const sortedItems = items.sort(sorter);
 * console.log(sortedItems);
 * // Output: [{ priority: 'high' }, { priority: 'medium' }, { priority: 'low' }]
 *
 * @param {T[]} order - The array specifying the order of items.
 * @param {(item: U) => T} getValue - A function to extract the value to compare from each item.
 * @returns {(item1: U, item2: U) => number} - A comparator function for sorting.
 */
export const getArraySorterFromArray = <T extends string | number | symbol, U>(
  order: T[] = [],
  getValue: (item: U) => T
): ((item1: U, item2: U) => number) => {
  const orderMap: Record<T, number> = order.reduce((map, item, index) => {
    map[item] = index;
    return map;
  }, {} as Record<T, number>);

  return (item1: U, item2: U): number => {
    return orderMap[getValue(item1)] - orderMap[getValue(item2)];
  };
};
