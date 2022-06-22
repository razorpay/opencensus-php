import React from 'react';
import { compose } from 'redux';
import { connect } from 'react-redux';

import Slider, { SliderDots } from 'common/new-ui/Slider';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import Landing from 'merchant/components/OnBoarding/Slides/Landing';
import Features from 'merchant/components/OnBoarding/Slides/Features';
import onBoardingHOC, { OnBoardingWrapper } from 'merchant/components/OnBoarding';

import { RZPFeatures } from 'merchant/helpers/data';

import { getItem, setItem } from 'common/utils/localStorage';
import { trackOptimizerEvents } from 'merchant/views/Navigator/track';
import { getOptimizerOnboardingStorageKey } from '../util';

import { FEATURES_DATA, FEATURES_LINKS, MORE_FEATURES_LINK } from './constants';

class OptimizerOnBoarding extends React.Component {
  constructor() {
    super();
    this.state = {
      isTrialRequested: false,
      isPending: false,
    };
  }

  componentDidMount() {
    this.onMount();
  }

  onMount = () => {
    const isTrialRequested = !!getItem(getOptimizerOnboardingStorageKey(this.props.user));
    this.setState({ isTrialRequested });
  };

  requestTrial = () =>
    new Promise((resolve) => {
      setTimeout(() => {
        setItem(getOptimizerOnboardingStorageKey(this.props.user), 1);
        trackOptimizerEvents({
          objectName: 'Optimizer Request Free Trial',
          actionName: 'clicked',
          screen: 'Optimizer - Onboarding',
        });
        resolve('Success');
      }, 1000);
    });

  handleRequestTrialClick = () => {
    this.setState({ isPending: true });
    this.requestTrial()
      .then((res) => {
        if (res === 'Success') {
          this.setState({ isTrialRequested: true });
        }
      })
      .finally(() => {
        this.setState({ isPending: false });
      });
  };

  renderRequestedTrialButton = () => (
    <Button className="joined-button">
      <i className="i i-tick" />
      Free Trial Requested
    </Button>
  );

  getNextBtnProp = () => () => {
    const { isTrialRequested, isPending } = this.state;
    return isTrialRequested ? (
      this.renderRequestedTrialButton()
    ) : (
      <AsyncBtn.Primary className="Forward-Button" onClick={this.handleRequestTrialClick}>
        Request Free Trial
        {isPending && <span className="spin-btn white visible" />}
      </AsyncBtn.Primary>
    );
  };

  getCalloutElement = () => {
    const { isTrialRequested, isPending } = this.state;
    return (
      <div className="Button-Container callout-button">
        {isTrialRequested ? (
          this.renderRequestedTrialButton()
        ) : (
          <AsyncBtn.Secondary onClick={this.handleRequestTrialClick}>
            Request Free Trial
            {isPending && <span className="spin-btn visible" />}
          </AsyncBtn.Secondary>
        )}
      </div>
    );
  };

  render() {
    const { active } = this.props;
    return (
      <OnBoardingWrapper className="Optimizer">
        <Slider active={active} afterSlide={getOnBoardingSliderDots}>
          {(sliderProps) => (
            <Landing
              {...sliderProps}
              title="Optimizer"
              feature={RZPFeatures.OPTIMIZER}
              imageUrl="https://razorpay.com/assets/optimizer/banner-illustration.png"
              desc="Get higher success rates for your business, reduce your transaction costs, and increase your revenue with our smart multi-gateway payments management platform."
              callout={this.getCalloutElement()}
            />
          )}

          {(sliderProps) => (
            <Features
              {...sliderProps}
              title="What makes Optimizer great?"
              nextBtn={this.getNextBtnProp(sliderProps)}
              feature={RZPFeatures.OPTIMIZER}
              featureLinks={FEATURES_LINKS}
              features={FEATURES_DATA}
              moreFeaturesLink={MORE_FEATURES_LINK}
            />
          )}
        </Slider>
      </OnBoardingWrapper>
    );
  }
}

function getOnBoardingSliderDots(sliderProps) {
  return <SliderDots {...sliderProps} />;
}

export default compose(
  connect(
    (state) => ({
      user: state.session.user,
    }),
    null,
  ),
  onBoardingHOC({
    feature: RZPFeatures.OPTIMIZER,
  }),
)(OptimizerOnBoarding);
