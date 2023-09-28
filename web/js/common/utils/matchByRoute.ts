import { matchPath } from 'react-router-dom';

/**
 * @deprecated Please refactor your components w.r.t new rr6 version. Check Content.js for reference.
 * @param currPathname Current pathname via location prop.
 * @param refPathname Pathname to match with.
 * @param exact Defaults to false
 * @returns {string}
 */

export const matchByRoute = (currPathname: string, refPathname: string, exact = false): string => {
  const activeMatchedSuffix = exact ? '' : '/*';
  const pathToMatch = refPathname + activeMatchedSuffix;
  const outputPath = matchPath(pathToMatch, currPathname) ? activeMatchedSuffix : pathToMatch;
  return outputPath;
};
