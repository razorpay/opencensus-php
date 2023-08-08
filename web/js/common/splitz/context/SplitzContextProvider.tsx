import React, { useEffect, createContext } from 'react';
import { getVariant, getVariants, initABService } from 'common/splitz/services';
import { withRouter } from 'react-router-dom';
import {
  ActiveDashboardType,
  SpiltzContextState,
  SpiltzServiceProviderProps,
  VariantConfigArgs,
} from 'common/splitz/types';
import { splitzConfig } from 'common/splitz/configs';
import { API_BASE_URL, REF_MID } from 'common/splitz/constants';
import { useSplitzReducer } from 'common/splitz/hooks/useSplitzReducer';

export const SpiltzContext = createContext({} as SpiltzContextState);

const SpiltzServiceProviderComponent = ({
  children,
  dashboardType,
  customLoader,
  history,
}: SpiltzServiceProviderProps): JSX.Element => {
  const { abExperiments, isInitialized, setABExperiments, setInitialized } = useSplitzReducer();

  const isExperimentEvaluated = (experimentHashKey: string) => {
    return Boolean(abExperiments[experimentHashKey]);
  };

  const evaluateExperiment = async (experimentToEvaluate: VariantConfigArgs) => {
    try {
      const evaluatedExperiment = await getVariant(experimentToEvaluate);
      setABExperiments({
        [experimentToEvaluate.uniqueHashKey]: evaluatedExperiment,
      });
    } catch {
      // TODO: eventTracking, etc
    }
  };

  const bulkEvaluateExperiments = async (experimentsToEvaluate: VariantConfigArgs[]) => {
    try {
      const evaluatedExp = await getVariants(experimentsToEvaluate);
      setABExperiments(evaluatedExp);
    } catch {
      // TODO: eventTracking, etc
    }
  };

  const getActiveDashboard = (): ActiveDashboardType => {
    if (!dashboardType) {
      throw new Error(
        'Missing "dashboardType" prop. Please make sure its passed to "SplitzRoutesBasedService".',
      );
    }
    switch (dashboardType) {
      // internal segmentation of merchant dashboard
      case 'merchant': {
        const pathName = history.location.pathname;
        if (pathName.startsWith('/partners')) {
          return 'partner';
        } else {
          return 'product';
        }
      }
      default:
        return dashboardType as ActiveDashboardType;
    }
  };

  useEffect(() => {
    initABService({
      apiBaseUrl: API_BASE_URL,
      merchantId: REF_MID,
    });

    const experimentMapToEvalOnInit = splitzConfig.onInit;

    const defaultExperimentsToEval = ['merchant', 'linkedAccount'].includes(dashboardType)
      ? experimentMapToEvalOnInit.default
      : [];
    const refDashboardExperimentsToEval = experimentMapToEvalOnInit[dashboardType];

    if (defaultExperimentsToEval.length || refDashboardExperimentsToEval.length) {
      const experimentsToEval = [...defaultExperimentsToEval, ...refDashboardExperimentsToEval];

      getVariants(experimentsToEval).then((initialyEvaluatedExperiments) => {
        setABExperiments(initialyEvaluatedExperiments);
        setInitialized(true);
      });
    } else {
      setInitialized(true);
    }
  }, []);

  return (
    <SpiltzContext.Provider
      value={{
        isInitialized,
        abExperiments,
        evaluateExperiment,
        bulkEvaluateExperiments,
        isExperimentEvaluated,
        activeDashboard: getActiveDashboard(),
      }}
    >
      {isInitialized ? children : customLoader ? customLoader() : <div id="splash" />}
    </SpiltzContext.Provider>
  );
};

/**
 * Please wrap your main root app with this.
 *
 * This component will handle initialization of splitz service for your app. Also, it will evaluate all the experiments passed as value for onInit key in splitz config.
 *
 * You can consume your evaluated experiment via `useSplitzService` hook or `withSplitzService` HOC.
 */
export const SpiltzServiceProvider = withRouter(SpiltzServiceProviderComponent);
