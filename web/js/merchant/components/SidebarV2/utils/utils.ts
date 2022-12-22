export const swapElements = (array, indexA, indexB) => {
  if (array[indexA] && array[indexB]) {
    const temp = array[indexA];
    array[indexA] = array[indexB];
    array[indexB] = temp;
  }
  return array;
};
