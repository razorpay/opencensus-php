import React from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';

import Button from 'common/new-ui/Button';
import Slider, { SliderDots } from 'common/new-ui/Slider';
import OnBoarding, {
  OnBoardingWrapper,
  FeatureEnableSliderButton,
  SkipAndGetStartedButton,
  getIsAllowedResetBoarding,
} from 'merchant/components/OnBoarding';
import Features from 'merchant/components/OnBoarding/Slides/Features';
import Landing from 'merchant/components/OnBoarding/Slides/Landing';
import { setQuickGuideIsClosedInLocalStorage } from 'merchant/components/QuickGuide';
import { RZPFeatures } from 'merchant/helpers/data';
import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/reducers/onboarding';
import { fetchUser } from 'merchant/reducers/session';
import { openModal, closeModal } from 'merchant_common/reducers/modals';

import KYCAlertModal from './KYCAlertModal';
import { setItem } from 'common/utils/localStorage';
import { switchMode } from '@libs/shared-utils';
import { FEATURES_DATA, FEATURES_LINKS } from './data';

class MarketPlaceOnBoarding extends React.Component {
  get showKYCModal() {
    return !this.props.isTestMode && !this.props.user.isAccepted;
  }

  switchToTestMode = () => {
    const { user } = this.props;
    switchMode(user.current, 'test');
    window.location.reload();
  };

  showKYCAlertModal = () => {
    this.props.openModal({
      size: 'small',
      component: (
        <KYCAlertModal
          user={this.props.user}
          switchToTestMode={this.switchToTestMode}
          closeModal={this.props.closeModal}
        />
      ),
    });
  };

  closeOnboarding = () => {
    if (!this.props.routeProductOnBoarding.isTour) {
      setQuickGuideIsClosedInLocalStorage(RZPFeatures.ROUTE, false);
    }

    this.props.closeOnboarding();
  };

  getNextBtnProp = (sliderProps) => () => {
    if (this.showKYCModal) {
      return (
        <Button.Primary className="Forward-Button" onClick={this.showKYCAlertModal}>
          Get Started
        </Button.Primary>
      );
    }

    const props = {
      feature: RZPFeatures.ROUTE,
      onClick: this.props.closeOnboarding,
      page: sliderProps.active,
    };

    if (this.props.user.isMarketplaceEnabled) {
      props.isLocalEnabler = true;
      props.onClick = this.closeOnboarding;
    }

    return <FeatureEnableSliderButton {...props} />;
  };

  renderSkipButton = (sliderProps) => {
    if (this.showKYCModal) {
      return (
        <Button.Transparent onClick={this.showKYCAlertModal}>
          Skip And Get Started
        </Button.Transparent>
      );
    }

    const btnProps = {
      feature: RZPFeatures.ROUTE,
      page: sliderProps.active,
      onClick: this.props.closeOnboarding,
      isTour: this.props.routeProductOnBoarding.isTour,
    };

    if (this.props.user.isMarketplaceEnabled) {
      btnProps.isLocalEnabler = true;
      btnProps.onClick = this.closeOnboarding;
    }

    return <SkipAndGetStartedButton {...btnProps} />;
  };

  render() {
    return (
      <OnBoardingWrapper className="Route">
        <Slider
          active={this.props.active}
          afterSlide={getOnBoardingSliderDots(this.renderSkipButton)}
        >
          {(sliderProps) => (
            <Landing
              {...sliderProps}
              title="Route"
              feature={RZPFeatures.ROUTE}
              imageUrl="https://razorpay.com/assets/route/route-landing.svg"
              desc="Easily split incoming payments into various linked accounts. Manage settlements, reconciliation and refunds easily by having complete control over money flow."
            />
          )}

          {(sliderProps) => (
            <Features
              {...sliderProps}
              title="What makes Route great?"
              nextBtn={this.getNextBtnProp(sliderProps)}
              featureLinks={FEATURES_LINKS}
              features={FEATURES_DATA}
              feature={RZPFeatures.ROUTE}
            />
          )}
        </Slider>
      </OnBoardingWrapper>
    );
  }
}

export default compose(
  connect(
    (state) => ({
      user: state.session.user,
      isTestMode: state.session.mode === 'test',
      routeProductOnBoarding: getCurrentProductOnBoardingDetails(state, RZPFeatures.ROUTE),
    }),
    {
      fetchUser,
      openModal,
      closeModal,
      handleProductQuickGuide,
    },
  ),
  // eslint-disable-next-line
  OnBoarding({
    feature: RZPFeatures.ROUTE,
  }),
)(MarketPlaceOnBoarding);

function getOnBoardingSliderDots(renderSkipButton) {
  return (sliderProps) => <SliderDots {...sliderProps}>{renderSkipButton(sliderProps)}</SliderDots>;
}

export function getIsAllowedResetRouteBoarding({ transfers, accounts }) {
  if (accounts.loading || transfers.loading || transfers.items.length || accounts.accounts.length) {
    return false;
  }

  return getIsAllowedResetBoarding(RZPFeatures.ROUTE);
}
