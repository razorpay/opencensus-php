import { connect } from 'react-redux';

import LocalStorageService from 'rzp/utils/localStorage';

import Button from 'component/Button';

import FeatureEnableButton from 'merchant/components/OnBoarding/FeatureEnableButton';

import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/modules/onboarding';

export default params => {
  const { feature: FEATURE } = params;

  let _WrappedComponent;
  @connect(
    state => ({
      user: state.session.user,
      mode: state.session.mode,
      currentOnboarding: getCurrentProductOnBoardingDetails(state, FEATURE),
    }),
    {
      handleProductQuickGuide,
    }
  )
  class OnBoardingHOC extends React.Component {
    constructor(props) {
      super(props);

      const merchant = props.user.merchants[props.user.current];

      const { lastScreen, lastVisitedTime } = getOnBoardingKeys({
        merchantId: merchant.id,
        mode: props.mode,
        feature: FEATURE,
      });

      this.LAST_VISITED_SCREEN_KEY = lastScreen;
      this.LAST_VISITED_TIME_KEY = lastVisitedTime;

      this.state = {
        active: this.isActive,
      };
    }

    get isActive() {
      return LocalStorageService.getItem(this.LAST_VISITED_SCREEN_KEY) || 0;
    }

    componentDidMount() {
      const reset = isAllowedResetBoarding({
        feature: FEATURE,
        mode: this.props.mode,
        merchantId: this.props.user.merchants[this.props.user.current].id,
      });

      if (reset) {
        LocalStorageService.setItem(this.LAST_VISITED_SCREEN_KEY, 0);

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

    onSlideChange = index => {
      LocalStorageService.setItem(this.LAST_VISITED_SCREEN_KEY, index);
    };

    closeOnboarding = ({ isFeatureEnabler }) => {
      LocalStorageService.setItem(this.LAST_VISITED_TIME_KEY, Date.now());
      LocalStorageService.setItem(this.LAST_VISITED_SCREEN_KEY, 0);

      if (isFeatureEnabler) return;

      this.props.handleProductQuickGuide({
        ...this.props.currentOnboarding,
        showOnboarding: false,
        isTour: true,
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

export const FeatureEnableSliderButton = ({ feature, onClick, page }) => {
  if (feature) {
    return (
      <FeatureEnableButton.Primary
        feature={feature}
        class="Forward-Button"
        pendingText="Enabling..."
        onClick={(...args) => {
          window.rzpAnalytics({
            eventCategory: `Product Introduction (${feature})`,
            eventAction: `Page ${page} - Get Started CTA`,
          });

          onClick(args);
        }}
      >
        Get Started
      </FeatureEnableButton.Primary>
    );
  }

  return (
    <Button.Primary
      feature={feature}
      class="Forward-Button"
      iconAfter="arrow-forward"
      pendingText="Enabling..."
      onClick={(...args) => {
        window.rzpAnalytics({
          eventCategory: `Product Introduction (${feature})`,
          eventAction: `Page ${page} - Get Started CTA`,
        });

        onClick(args);
      }}
    >
      Get Started
    </Button.Primary>
  );
};

export const NextButton = ({ feature, onClick, page }) => (
  <Button.Primary
    feature={feature}
    class="Forward-Button"
    iconAfter="arrow-forward"
    pendingText="Enabling..."
    onClick={(...args) => {
      window.rzpAnalytics({
        eventCategory: `Product Introduction (${feature})`,
        eventAction: `Page ${page} - Next CTA`,
      });

      onClick(args);
    }}
  >
    Get Started
  </Button.Primary>
);

export const SkipAndGetStartedButton = ({ feature, onClick, page }) => {
  if (!feature) {
    return (
      <Button.Transparent
        onClick={(...args) => {
          window.rzpAnalytics({
            eventCategory: `Product Introduction (${feature})`,
            eventAction: `Page ${page} - Skip and Get Started`,
          });

          onClick(args);
        }}
      >
        Skip And Get Started
      </Button.Transparent>
    );
  }

  return (
    <FeatureEnableButton.Transparent
      onClick={(...args) => {
        window.rzpAnalytics({
          eventCategory: `Product Introduction (${feature})`,
          eventAction: `Page ${page} - Skip and Get Started`,
        });

        onClick(args);
      }}
      feature={feature}
    >
      Skip And Get Started
    </FeatureEnableButton.Transparent>
  );
};

export const getOnBoardingKeys = ({ mode, merchantId, feature }) => {
  const KEY = `rzp_onboarding_${merchantId}_${mode}_${feature}`;

  return {
    key: KEY,
    lastScreen: `${KEY}_last_visited_screen`,
    lastVisitedTime: `${KEY}_last_visited_time`,
  };
};

export const isAllowedResetBoarding = ({ mode, merchantId, feature }) => {
  const { lastVisitedTime } = getOnBoardingKeys({
    feature,
    mode,
    merchantId,
  });

  const lastVisitedTimeVal =
    Number(LocalStorageService.getItem(lastVisitedTime)) || void 0;

  const momentLastVisitedTime = moment(lastVisitedTimeVal),
    currentTime = moment(Date.now());

  return currentTime.diff(momentLastVisitedTime, 'days') >= 15;
};
