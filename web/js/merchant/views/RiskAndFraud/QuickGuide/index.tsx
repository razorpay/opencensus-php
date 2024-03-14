import React from 'react';

import withQuickGuide, {
  setQuickGuideIsClosedInLocalStorage,
  getQuickGuideIsClosedFromLocalStorage,
} from 'merchant/components/QuickGuide';
import { RZPFeatures } from 'merchant/helpers/data';
import { RiskAndFraudQuickGuideProps } from 'merchant/views/RiskAndFraud/RiskAnalytics/types';

import QuickGuideStep from './QuickGuideStep';
import { getQuickGuideData } from './constant';

const RiskAndFraudQuickGuide = ({
  handleProductQuickGuide,
  currentOnboarding,
  org,
}: RiskAndFraudQuickGuideProps) => {
  const onCloseClick = () => {
    setQuickGuideIsClosedInLocalStorage(RZPFeatures.RISK_AND_FRAUD);
    handleProductQuickGuide({
      ...currentOnboarding,
      isQuickGuideOpen: false,
      isTour: false,
    });
  };
  return (
    <QuickGuideStep
      title="Understanding Frauds and Disputes"
      tiles={getQuickGuideData(org.business_name)}
      onCloseClick={onCloseClick}
    />
  );
};

export const getRiskAndFraudQuickGuideIsClosed = (): boolean => {
  return getQuickGuideIsClosedFromLocalStorage(RZPFeatures.RISK_AND_FRAUD);
};

const quickGuideSettings = {
  feature: RZPFeatures.RISK_AND_FRAUD,
  data_points: [],
};

export default withQuickGuide(quickGuideSettings)(RiskAndFraudQuickGuide);
