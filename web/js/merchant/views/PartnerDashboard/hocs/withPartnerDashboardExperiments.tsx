import React, { ComponentType } from 'react';
import { SpiltzContextState } from 'common/splitz/types';
import usePartnerDashboardExperiments from 'merchant/views/PartnerDashboard/hooks/usePartnerDashboardExperiments';

/**
 * A HOC wrapper for consuming partner dashboard's specific experiments
 *
 * For functional components use `usePartnerDashboardExperiments` hook instead.
 *
 */
const withPartnerDashboardExperiments =
  <
    T extends {
      experiments: SpiltzContextState;
    },
  >(
    Component: ComponentType<T>,
  ) =>
  (props: T): JSX.Element => {
    const experiments = usePartnerDashboardExperiments();
    return <Component {...props} experiments={experiments} />;
  };

export default withPartnerDashboardExperiments;
