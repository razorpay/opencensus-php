import { connect } from 'react-redux';

import { RZPFeatures } from 'rzp/utils/constants';

import Slider, { SliderDots } from 'component/Slider';

import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/modules/onboarding';

import Landing from 'merchant/components/OnBoarding/Slides/Landing';
import Features from 'merchant/components/OnBoarding/Slides/Features';
import OnBoarding, {
  FeatureEnableSliderButton,
  OnBoardingWrapper,
  SkipAndGetStartedButton,
  getIsAllowedResetBoarding,
} from 'merchant/components/OnBoarding';
import { setQuickGuideIsClosedInLocalStorage } from 'merchant/components/QuickGuide';

import { FEATURES_DATA, FEATURES_LINKS, PROS } from './data';

@connect(
  state => ({
    user: state.session.user,
    VAProductOnBoarding: getCurrentProductOnBoardingDetails(
      state,
      RZPFeatures.VA
    ),
  }),
  { handleProductQuickGuide }
)
@OnBoarding({
  feature: RZPFeatures.VA,
})
export default class InvoicesOnBoarding extends React.Component {
  getNextButton = sliderProps => () => {
    const props = {
      feature: RZPFeatures.VA,
      onClick: this.props.closeOnboarding,
      page: sliderProps.active,
    };

    if (this.props.user.isVirtualAccountsEnabled) {
      props.isLocalEnabler = true;
      props.onClick = this.closeOnboarding;
    }

    return <FeatureEnableSliderButton {...props} />;
  };

  renderSkipButton = sliderProps => {
    const props = {
      feature: RZPFeatures.VA,
      onClick: this.props.closeOnboarding,
      page: sliderProps.active,
      isTour: this.props.VAProductOnBoarding.isTour,
    };

    if (this.props.user.isVirtualAccountsEnabled) {
      props.isLocalEnabler = true;
      props.onClick = this.closeOnboarding;
    }

    return <SkipAndGetStartedButton {...props} />;
  };

  closeOnboarding = () => {
    if (!this.props.VAProductOnBoarding.isTour) {
      setQuickGuideIsClosedInLocalStorage(RZPFeatures.VA, false);
    }

    this.props.closeOnboarding();
  };

  render() {
    const { active } = this.props;

    return (
      <OnBoardingWrapper class="SmartCollect">
        <Slider active={active}>
          {sliderProps => (
            <Landing
              {...sliderProps}
              feature={RZPFeatures.VA}
              title="Smart Collect"
              imageUrl="/dist/css/assets/product_onboarding/smart_collect.svg"
              desc="Create and send GST compliant invoices that your customers can pay online instantly."
              pros={PROS}
            />
          )}

          {sliderProps => (
            <Features
              {...sliderProps}
              feature={RZPFeatures.VA}
              title="What makes Smart Collect great?"
              nextBtn={this.getNextButton(sliderProps)}
              featureLinks={FEATURES_LINKS}
              features={FEATURES_DATA}
            />
          )}

          {sliderProps => (
            <SliderDots {...sliderProps}>
              {this.renderSkipButton(sliderProps)}
            </SliderDots>
          )}
        </Slider>
      </OnBoardingWrapper>
    );
  }
}

export function getIsAllowedResetVAOnBoarding({ items, loading }) {
  if (items.length || loading) {
    return false;
  }

  return getIsAllowedResetBoarding(RZPFeatures.VA);
}
