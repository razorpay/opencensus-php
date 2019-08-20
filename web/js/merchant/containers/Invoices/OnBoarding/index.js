import { connect } from 'react-redux';

import { RZPFeatures } from 'rzp/utils/constants';

import Slider, { SliderDots } from 'component/Slider';

import Landing from 'merchant/components/OnBoarding/Screens/Landing';
import Features from 'merchant/components/OnBoarding/Screens/Features';
import OnBoarding, {
  FeatureEnableSliderButton,
  OnBoardingWrapper,
  SkipAndGetStartedButton,
  getIsAllowedResetBoarding,
  setOnBoardingDataInLocalState,
} from 'merchant/components/OnBoarding';

import { setQuickGuideIsClosedInLocalStorage } from 'merchant/components/QuickGuide';

import { FEATURES_DATA, FEATURES_LINKS } from './data';

@connect(state => ({
  user: state.session.user,
}))
@OnBoarding({
  feature: RZPFeatures.INVOICE,
})
export default class InvoicesOnBoarding extends React.Component {
  getNextBtnProp = sliderProps => () => {
    return (
      <FeatureEnableSliderButton
        isLocalEnabler
        feature={RZPFeatures.INVOICE}
        page={sliderProps.active}
        onClick={this.closeOnboarding}
      />
    );
  };

  closeOnboarding = () => {
    if (this.props.user.isInvoicesEnabled) {
      setQuickGuideIsClosedInLocalStorage(RZPFeatures.INVOICE, false);
    }

    this.props.closeOnboarding();
  };

  render() {
    const { active, onSlideChange } = this.props;

    return (
      <OnBoardingWrapper class="Invoices">
        <Slider active={active} onSlideChange={onSlideChange}>
          {sliderProps => (
            <Landing
              {...sliderProps}
              title="Invoices"
              imageUrl="https://razorpay.com/assets/invoices/banner.svg"
              desc="Create and send GST compliant invoices that your customers can pay online instantly."
            />
          )}

          {sliderProps => (
            <Features
              {...sliderProps}
              title="What makes Invoices great?"
              nextBtn={this.getNextBtnProp(sliderProps)}
              featureLinks={FEATURES_LINKS}
              features={FEATURES_DATA}
            />
          )}

          {sliderProps => (
            <SliderDots {...sliderProps}>
              <SkipAndGetStartedButton
                feature={RZPFeatures.INVOICE}
                page={sliderProps.active}
                onClick={this.closeOnboarding}
                isLocalEnabler={user.isInvoicesEnabled}
              />
            </SliderDots>
          )}
        </Slider>
      </OnBoardingWrapper>
    );
  }
}

export function getIsAllowedResetInvoicesOnBoarding({ invoices, items }) {
  if (invoices.invoices.length || items.items.length) {
    return false;
  }

  return getIsAllowedResetBoarding(RZPFeatures.INVOICE);
}

export function getIsInvoicesEnabled({ user, invoices, items }) {
  if (
    user.isInvoicesEnabled ||
    invoices.invoices.length ||
    invoices.loading ||
    items.items.length ||
    items.Landing
  ) {
    return true;
  }

  if (invoices.invoices.length || items.items.length) {
    setOnBoardingDataInLocalState({
      feature: RZPFeatures.INVOICE,
      data: {
        isEnabled: true,
        lastVisitedTime: Date.now(),
      },
    });

    return true;
  }

  return false;
}
