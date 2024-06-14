import React from 'react';

import { ShowWhen } from 'merchant_common/components/RouteGuard';

import ExporterRewardsOnBoarding from './Onboarding';
import { ContainerWrapper } from './components/styled';
import { exporterRewardsOnboardingStatus } from '../../utils/conditionUtils';

const ExporterRewards = () => {
  return (
    <ContainerWrapper>
      <ShowWhen additionalCondition={exporterRewardsOnboardingStatus}>
        <ExporterRewardsOnBoarding />
      </ShowWhen>
    </ContainerWrapper>
  );
};

export default ExporterRewards;
