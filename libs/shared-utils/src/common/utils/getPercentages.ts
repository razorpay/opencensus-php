import { getPercentage } from "./getPercentage";

export const getPercentages = (...args) => {
  /*
   * Given Number arguments, returns a dictionary
   * with keys as given numbers and values as the
   * percentage of value compared to sum
   *
   * eg:
   * getPercentages(1,2,3); // => {1: "16.67", 2: "33.33", 3: "50"}
   */

  const sum = args.reduce((sum, item) => item + sum, 0);

  return args.reduce((result, item) => {
    result[item] = getPercentage(sum, item);

    return result;
  }, {});
};