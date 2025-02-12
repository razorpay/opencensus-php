import React from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';

import Slider, { SliderDots } from 'common/new-ui/Slider';
import OnBoarding, {
  FeatureEnableSliderButton,
  OnBoardingWrapper,
  SkipAndGetStartedButton,
  getIsAllowedResetBoarding,
  setOnBoardingDataInLocalState,
} from 'merchant/components/OnBoarding';
import Features from 'merchant/components/OnBoarding/Slides/Features';
import Landing from 'merchant/components/OnBoarding/Slides/Landing';
import { setQuickGuideIsClosedInLocalStorage } from 'merchant/components/QuickGuide';
import { RZPFeatures } from 'merchant/helpers/data';
import { ORG_CUSTOM_CODE_MAP } from 'merchant/models/User';
import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/reducers/onboarding';

import { FEATURES_DATA, FEATURES_LINKS, CURLEC_FEATURES_DATA } from './data';

// i18
export const LANDING_PAGE_DESC = {
  [ORG_CUSTOM_CODE_MAP.RAZORPAY]:
    'Create and send GST compliant and International invoices your customers can pay online instantly.',
  [ORG_CUSTOM_CODE_MAP.CURLEC]: 'Create and send invoices your customers can pay online instantly.',
};

// i18
const FEATURE_LINKS_MAPS = {
  [ORG_CUSTOM_CODE_MAP.RAZORPAY]: FEATURES_LINKS,
  [ORG_CUSTOM_CODE_MAP.CURLEC]: [],
};

// i18
const FEATURE_DATA_MAPS = {
  [ORG_CUSTOM_CODE_MAP.RAZORPAY]: FEATURES_DATA,
  [ORG_CUSTOM_CODE_MAP.CURLEC]: CURLEC_FEATURES_DATA,
};

class InvoicesOnBoarding extends React.Component {
  getNextBtnProp = (sliderProps) => () => {
    return (
      <FeatureEnableSliderButton
        isLocalEnabler
        feature={RZPFeatures.INVOICE}
        page={sliderProps.active}
        // eslint-disable-next-line react/no-this-in-sfc
        onClick={this.closeOnboarding}
      />
    );
  };

  closeOnboarding = () => {
    if (this.props.user.isInvoicesEnabled && !this.props.invoicesProductOnBoarding.isTour) {
      setQuickGuideIsClosedInLocalStorage(RZPFeatures.INVOICE, false);
    }

    this.props.closeOnboarding();
  };

  render() {
    const { active, invoicesProductOnBoarding, org } = this.props;
    const orgCode = org.custom_code.toLowerCase();
    const featureLinks = FEATURE_LINKS_MAPS[orgCode] || FEATURES_LINKS;
    const description = LANDING_PAGE_DESC[orgCode];
    const featureData = FEATURE_DATA_MAPS[orgCode] || FEATURES_DATA;

    return (
      <OnBoardingWrapper className="Invoices">
        <Slider
          active={active}
          afterSlide={getOnBoardingSliderDots({
            invoicesProductOnBoarding,
            closeOnboarding: this.closeOnboarding,
          })}
        >
          {(sliderProps) => (
            <Landing
              {...sliderProps}
              feature={RZPFeatures.INVOICE}
              title="Invoices"
              imageUrl="https://razorpay.com/assets/invoices/banner.svg"
              desc={description}
              businessName={org.business_name}
            />
          )}

          {(sliderProps) => (
            <Features
              {...sliderProps}
              title="What makes Invoices great?"
              feature={RZPFeatures.INVOICE}
              nextBtn={this.getNextBtnProp(sliderProps)}
              featureLinks={featureLinks}
              features={featureData}
            />
          )}
        </Slider>
      </OnBoardingWrapper>
    );
  }
}

function getOnBoardingSliderDots({ closeOnboarding, invoicesProductOnBoarding }) {
  return (sliderProps) => (
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
  if (invoices.invoices.length || items.items.length || invoices.loading || items.loading) {
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

export default compose(
  connect(
    (state) => ({
      user: state.session.user,
      org: state.session.org,
      invoicesProductOnBoarding: getCurrentProductOnBoardingDetails(state, RZPFeatures.INVOICE),
    }),
    { handleProductQuickGuide },
  ),
  // eslint-disable-next-line
  OnBoarding({
    feature: RZPFeatures.INVOICE,
  }),
)(InvoicesOnBoarding);
