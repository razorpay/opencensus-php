import React, { useEffect, useMemo, Suspense } from 'react';
import { withRouter } from 'common/deprecated/withRouter';
import { useSplitzService } from 'common/splitz';
import { SpiltzServiceProviderProps, VariantConfigArgs } from 'common/splitz/types';
import { splitzConfig } from 'common/splitz/configs';
import { matchRoutes } from 'common/splitz/utils';
import { SplitzEvalLoader } from 'common/splitz/components';

const SplitzRoutesServiceComponent = ({
  children,
  history,
  customLoader,
}: Omit<SpiltzServiceProviderProps, 'dashboardType'>) => {
  const { abExperiments, isInitialized, bulkEvaluateExperiments, activeDashboard } =
    useSplitzService();

  const experimentsInMatchedRoute = useMemo(() => {
    // using map to make sure exps are unique
    const remainingExperimentsToEvaluate = {};

    for (const passedRouteConfig of splitzConfig.routeBased) {
      const dashboardTypesToMatch = passedRouteConfig?.matchByDashboard || [
        'product',
        'partner',
        'linkedAccount',
      ];

      if (dashboardTypesToMatch.includes(activeDashboard)) {
        const isRouteMatched = matchRoutes(
          passedRouteConfig.routesToMatch,
          history.location.pathname,
        );
        if (isRouteMatched) {
          for (const refExperiment of passedRouteConfig.abExperiments) {
            remainingExperimentsToEvaluate[refExperiment.uniqueHashKey] = refExperiment;
          }
        }
      }
    }

    const remainingExperimentsToEvaluateArr: VariantConfigArgs[] = Object.values(
      remainingExperimentsToEvaluate,
    );

    return remainingExperimentsToEvaluateArr;
  }, [history.location.pathname]);

  const experimentsToEvaluate = experimentsInMatchedRoute.filter(
    (refExperiment) => !Boolean(abExperiments[refExperiment.uniqueHashKey]),
  );

  const evaluateMatchedConfig = async () => {
    if (experimentsToEvaluate.length) {
      await bulkEvaluateExperiments(experimentsToEvaluate);
    }
  };

  useEffect(() => {
    if (isInitialized) evaluateMatchedConfig();
  }, [experimentsInMatchedRoute, isInitialized]);

  return experimentsToEvaluate.length ? (
    customLoader ? (
      customLoader()
    ) : (
      <Suspense fallback={<></>}>
        <SplitzEvalLoader />
      </Suspense>
    )
  ) : (
    children
  );
};

/**
 * Please wrap your main root level component that change entirely w.r.t the routes.
 *
 * This component will handle evaluation of experiments on change of route as configured in the splitz config object. It will handle the loading part automatically.
 *
 * On load, you can consume your evaluated experiment via `useSplitzService` hook or `withSplitzService` HOC.
 */
export const SplitzRoutesBasedService = withRouter(SplitzRoutesServiceComponent as unknown as any);
