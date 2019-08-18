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

import { FEATURES_DATA, FEATURES_LINKS } from './data';

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
        onClick={this.props.closeOnboarding}
      />
    );
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
              <SkipAndGetStartedButton onClick={this.props.closeOnboarding} />
            </SliderDots>
          )}
        </Slider>
      </OnBoardingWrapper>
    );
  }
}

export function getIsAllowedResetInvoicesOnBoarding(invoices) {
  if (invoices.invoices.length || invoices.loading) {
    return false;
  }

  return getIsAllowedResetBoarding(RZPFeatures.INVOICE);
}

export function getIsInvoicesEnabled({ user, invoices }) {
  if (user.isInvoicesEnabled) {
    return true;
  }

  if (invoices.invoices.length) {
    setOnBoardingDataInLocalState({
      feature: RZPFeatures.INVOICE,
      data: {
        isEnabled: true,
        lastVisitedTime: Date.now(),
      },
    });

    return true;
  }

  if (invoices.loading) {
    return true;
  }

  return false;
}
