import { matchPath } from 'react-router';
import { ABVariant, ExperimentType, RouteMatchConfig, RouteObject } from './types';

export const evaluatedExperimentParser = (
  evaluatedExperiment: Pick<ABVariant, 'experiment_id' | 'variables'>,
): ExperimentType => ({
  experimentId: evaluatedExperiment.experiment_id,
  variables: evaluatedExperiment.variables.reduce(
    (prevVariable, currVariable) => ({
      ...prevVariable,
      [currVariable.key]: currVariable.value,
    }),
    {},
  ),
});

export const evaluatedBulkExperimentsParser = (
  evaluatedExperiments: unknown[],
  uniqueHashKeyMap: Record<string, string>,
): Record<string, ExperimentType> =>
  (evaluatedExperiments as ABVariant[]).reduce((prevExperiment, currExperiment) => {
    const uniqueHashKey = uniqueHashKeyMap[currExperiment.experiment_id];
    return {
      ...prevExperiment,
      [uniqueHashKey]: evaluatedExperimentParser(currExperiment),
    };
  }, {});

export const matchRoutes = (routes: RouteMatchConfig[], historyPathName: string): boolean => {
  const isRouteMatched = routes.find((route) => {
    switch (true) {
      case route instanceof RegExp:
        return (route as RegExp).test(historyPathName);
      case typeof route === 'string':
      case Boolean((route as RouteObject)?.path):
        return Boolean(matchPath(historyPathName, route as RouteObject));
      default:
        return false;
    }
  });

  return Boolean(isRouteMatched);
};

export const getBaseUrl = (urlString: string): string => {
  const url = new URL(urlString);
  const protocol = url.protocol;
  const host = url.host;

  return `${protocol}//${host}`;
};
