import React from 'react';
import { connect } from 'react-redux';

import imgRiskAnalyticsButton from 'assets/risk-analytics/risk-visibility-onboarding.png';
import Slider, { SliderDots } from 'common/new-ui/Slider';
import {
  FeatureEnableSliderButton,
  OnBoardingWrapper,
  SkipAndGetStartedButton,
  getIsAllowedResetBoarding,
  setOnBoardingDataInLocalState,
  getOnBoardingDataFromLocalState,
} from 'merchant/components/OnBoarding';
import Features from 'merchant/components/OnBoarding/Slides/Features';
import Landing from 'merchant/components/OnBoarding/Slides/Landing';
import { RZPFeatures } from 'merchant/helpers/data';
import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/reducers/onboarding';
import { getFeaturesData, FEATURES_LINKS } from 'merchant/views/RiskAndFraud/OnBoarding/constant';
import {
  GetIsRiskAndFraudEnabled,
  GetOnboardingSliderDots,
  RiskAndFraudOnboardingProps,
} from 'merchant/views/RiskAndFraud/RiskAnalytics/types';

const getOnBoardingSliderDots = ({
  closeOnboarding,
  riskAndFraudProductOnBoarding,
}: GetOnboardingSliderDots) => {
  return (sliderProps) => (
    <SliderDots {...sliderProps}>
      <SkipAndGetStartedButton
        isLocalEnabler
        onClick={closeOnboarding}
        feature={RZPFeatures.RISK_AND_FRAUD}
        page={sliderProps.active}
        isTour={riskAndFraudProductOnBoarding.isTour}
      />
    </SliderDots>
  );
};

export const setRiskAndFraudOnBoardingData = (isEnabled: boolean): void => {
  setOnBoardingDataInLocalState({
    feature: RZPFeatures.RISK_AND_FRAUD,
    data: {
      isEnabled,
      lastVisitedTime: Date.now(),
    },
  });
};

const RiskAndFraudOnBoarding = ({
  active,
  riskAndFraudProductOnBoarding,
  org,
  closeOnboarding,
}: RiskAndFraudOnboardingProps) => {
  const businessName = org.business_name ?? 'Razorpay';

  const getNextBtnProp = (sliderProps) => () => {
    return (
      <FeatureEnableSliderButton
        isLocalEnabler
        feature={RZPFeatures.RISK_AND_FRAUD}
        page={sliderProps.active}
        onClick={closeOnboarding}
      />
    );
  };

  return (
    <OnBoardingWrapper>
      <Slider
        active={active}
        afterSlide={getOnBoardingSliderDots({
          riskAndFraudProductOnBoarding,
          closeOnboarding,
        })}
      >
        {(sliderProps) => (
          <Landing
            {...sliderProps}
            title={`${businessName} Shield`}
            feature={RZPFeatures.RISK_AND_FRAUD}
            imageUrl={imgRiskAnalyticsButton}
            desc="Analyze fraudulent and disputed transactions, identify patterns and take actions to reduce risky transactions by identifying the highest fraud and dispute contributors."
            businessName={businessName}
          />
        )}

        {(sliderProps) => (
          <Features
            {...sliderProps}
            title="Understanding Frauds and Disputes"
            nextBtn={getNextBtnProp(sliderProps)}
            featureLinks={FEATURES_LINKS}
            feature={RZPFeatures.RISK_AND_FRAUD}
            features={getFeaturesData(businessName)}
          />
        )}
      </Slider>
    </OnBoardingWrapper>
  );
};

export default connect(
  (state) => ({
    org: state.session.org,
    riskAndFraudProductOnBoarding: getCurrentProductOnBoardingDetails(
      state,
      RZPFeatures.RISK_AND_FRAUD,
    ),
  }),
  { handleProductQuickGuide },
)(RiskAndFraudOnBoarding);

export const getIsAllowedResetRiskAndFraudOnBoarding = (): boolean => {
  return getIsAllowedResetBoarding(RZPFeatures.RISK_AND_FRAUD);
};

export const getIsRiskAndFraudEnabled = (): GetIsRiskAndFraudEnabled => {
  return getOnBoardingDataFromLocalState(RZPFeatures.RISK_AND_FRAUD).isEnabled;
};
