// Todo: delete this file, it's available in @dashboard/shared-utils
import { matchPath } from 'react-router';

import {
  ABVariant,
  ExperimentType,
  RouteMatchConfig,
  RouteObject,
  VariantConfigArgs,
} from 'common/splitz/types';
import { defaultRequestData, requestDataArgs } from './configs/requestDataConfig';

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
        return Boolean(matchPath(route as RouteObject, historyPathName));
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

export const isExperimentEnabled = (experiment: ExperimentType): boolean => {
  return experiment?.variables?.result === 'on';
};

export const getSplitzRequestData = (experiment: VariantConfigArgs) => {
  return experiment.requestData
    ? {
        ...experiment.requestData(requestDataArgs),
        ...defaultRequestData,
      }
    : defaultRequestData;
};

export const isInternalTestingEnabled = (experiment): boolean => {
  const isRazorpayMerchant = window.rzp_user?.email?.endsWith('@razorpay.com');
  return isRazorpayMerchant && isExperimentEnabled(experiment?.internal_testing_whitelisting);
};
