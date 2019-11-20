import { connect } from 'react-redux';

import { RZPFeatures } from 'merchant/helpers/data';

import Slider, { SliderDots } from 'common/new-ui/Slider';

import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/reducers/onboarding';

import Landing from 'merchant/components/OnBoarding/Slides/Landing';
import Features from 'merchant/components/OnBoarding/Slides/Features';
import OnBoarding, {
  FeatureEnableSliderButton,
  OnBoardingWrapper,
  SkipAndGetStartedButton,
  getIsAllowedResetBoarding,
  setOnBoardingDataInLocalState,
} from 'merchant/components/OnBoarding';

import { setQuickGuideIsClosedInLocalStorage } from 'merchant/components/QuickGuide';

import { FEATURES_DATA, FEATURES_LINKS } from './data';

@connect(
  state => ({
    user: state.session.user,
    invoicesProductOnBoarding: getCurrentProductOnBoardingDetails(
      state,
      RZPFeatures.INVOICE
    ),
  }),
  { handleProductQuickGuide }
)
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
    if (
      this.props.user.isInvoicesEnabled &&
      !this.props.invoicesProductOnBoarding.isTour
    ) {
      setQuickGuideIsClosedInLocalStorage(RZPFeatures.INVOICE, false);
    }

    this.props.closeOnboarding();
  };

  render() {
    const { active, invoicesProductOnBoarding } = this.props;

    return (
      <OnBoardingWrapper class="Invoices">
        <Slider
          active={active}
          afterSlide={getOnBoardingSliderDots({
            invoicesProductOnBoarding,
            closeOnboarding: this.closeOnboarding,
          })}
        >
          {sliderProps => (
            <Landing
              {...sliderProps}
              feature={RZPFeatures.INVOICE}
              title="Invoices"
              imageUrl="https://razorpay.com/assets/invoices/banner.svg"
              desc="Create and send GST compliant and International invoices your customers can pay online instantly."
            />
          )}

          {sliderProps => (
            <Features
              {...sliderProps}
              title="What makes Invoices great?"
              feature={RZPFeatures.INVOICE}
              nextBtn={this.getNextBtnProp(sliderProps)}
              featureLinks={FEATURES_LINKS}
              features={FEATURES_DATA}
            />
          )}
        </Slider>
      </OnBoardingWrapper>
    );
  }
}

function getOnBoardingSliderDots({
  closeOnboarding,
  invoicesProductOnBoarding,
}) {
  return sliderProps => (
    <SliderDots {...sliderProps}>
      <SkipAndGetStartedButton
        isLocalEnabler
        isTour={invoicesProductOnBoarding.isTour}
        feature={RZPFeatures.INVOICE}
        page={sliderProps.active}
        onClick={closeOnboarding}
      />
    </SliderDots>
  );
}

export function getIsAllowedResetInvoicesOnBoarding({ invoices, items }) {
  if (
    invoices.invoices.length ||
    items.items.length ||
    invoices.loading ||
    items.loading
  ) {
    return false;
  }

  return getIsAllowedResetBoarding(RZPFeatures.INVOICE);
}

function setInvoiceOnboardingData({ invoices, items }) {
  const isEnabled = Boolean(invoices.invoices.length || items.items.length);

  setOnBoardingDataInLocalState({
    feature: RZPFeatures.INVOICE,
    data: {
      isEnabled,
      lastVisitedTime: isEnabled && Date.now(),
    },
  });
}

export function getIsInvoicesEnabled({ user, invoices, items }) {
  if (user.isInvoicesEnabled === undefined) {
    if (invoices.loading || items.loading) {
      return true;
    }

    setInvoiceOnboardingData({ invoices, items });
  }

  return user.isInvoicesEnabled;
}
