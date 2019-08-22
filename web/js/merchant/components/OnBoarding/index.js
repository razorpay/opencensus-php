import { connect } from 'react-redux';

import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/modules/onboarding';

import {
  getIsAllowedResetBoarding,
  getOnBoardingKey,
  setOnBoardingDataInLocalState,
  getOnBoardingDataFromLocalState,
} from './utils';

import {
  NextButton,
  SkipAndGetStartedButton,
  FeatureEnableSliderButton,
} from './utilButtons';

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
        active: 0 || props.active,
      };
    }

    componentDidMount() {
      if (typeof window.hj === 'function') {
        window.hj('trigger', 'onboarding_intro');
        window.hj('tagRecording', [`${FEATURE}_onboarding`]);
      }
    }

    goTo = active => {
      this.setState({ active });
    };

    closeOnboarding = () => {
      setOnBoardingDataInLocalState({
        feature: FEATURE,
        data: {
          isEnabled: true,
          lastVisitedTime: Date.now(),
        },
      });
    };

    render() {
      return (
        <_WrappedComponent
          active={Number(this.state.active)}
          goTo={this.goTo}
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
