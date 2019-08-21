import { connect } from 'react-redux';

import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/modules/onboarding';

import {
  NextButton,
  SkipAndGetStartedButton,
  FeatureEnableSliderButton,
  getIsAllowedResetBoarding,
  getOnBoardingKey,
  setOnBoardingDataInLocalState,
  getOnBoardingDataFromLocalState,
} from './utils';

export default params => {
  const { feature: FEATURE } = params;

  let _WrappedComponent;
  @connect(
    state => ({
      currentOnboarding: getCurrentProductOnBoardingDetails(state, FEATURE),
    }),
    {
      handleProductQuickGuide,
    }
  )
  class OnBoardingHOC extends React.Component {
    constructor(props) {
      super(props);

      this.state = {
        active: this.isActive,
      };
    }

    get isActive() {
      const data = getOnBoardingDataFromLocalState(FEATURE);

      return data.lastVisitedScreen || 0;
    }

    componentDidMount() {
      const reset = getIsAllowedResetBoarding(FEATURE);

      if (reset) {
        setOnBoardingDataInLocalState({
          feature: FEATURE,
          data: {
            lastVisitedScreen: 0,
          },
        });

        this.setState({
          active: 0,
        });
      }

      if (typeof window.hj === 'function') {
        window.hj('trigger', 'onboarding_intro');
        window.hj('tagRecording', [`${FEATURE}_onboarding`]);
      }
    }

    goTo = active => {
      this.setState({ active }, () => this.onSlideChange(active));
    };

    onSlideChange = lastVisitedScreen => {
      setOnBoardingDataInLocalState({
        feature: FEATURE,
        data: {
          lastVisitedScreen,
        },
      });
    };

    closeOnboarding = () => {
      setOnBoardingDataInLocalState({
        feature: FEATURE,
        data: {
          isEnabled: true,
          lastVisitedScreen: 0,
          lastVisitedTime: Date.now(),
        },
      });
    };

    render() {
      return (
        <_WrappedComponent
          active={Number(this.state.active)}
          goTo={this.goTo}
          onSlideChange={this.onSlideChange}
          closeOnboarding={this.closeOnboarding}
          {...this.props}
        />
      );
    }
  }

  return function(WrappedComponent) {
    _WrappedComponent = WrappedComponent;

    return OnBoardingHOC;
  };
};

export const OnBoardingWrapper = ({ className, children }) => (
  <div class={`OnBoarding OnBoarding--${className}`}>{children}</div>
);

export {
  NextButton,
  SkipAndGetStartedButton,
  FeatureEnableSliderButton,
  getOnBoardingKey,
  setOnBoardingDataInLocalState,
  getOnBoardingDataFromLocalState,
  getIsAllowedResetBoarding,
};
