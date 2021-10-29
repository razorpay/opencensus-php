import React from 'react';
import RTracking from 'react-tracking';
import Button from 'common/new-ui/Button';

import FeatureEnableButton from './FeatureEnableButton';

class FeatureEnableSliderButton extends React.PureComponent {
  onClickFeatureEnableSliderButton = (...args) => {
    const { props } = this;

    window.rzpAnalytics({
      eventCategory: `Product Introduction (${props.feature})`,
      eventAction: `Page ${props.page} - Get Started CTA`,
    });

    // eslint-disable-next-line
    props.onClick && props.onClick(args);
  };

  render() {
    const { isLocalEnabler, feature, buttonText } = this.props;

    const extraProps = {
      feature,
    };

    if (isLocalEnabler) {
      extraProps.iconAfter = 'arrow-forward';
      extraProps.isLocalEnabler = true;
    }

    return (
      <FeatureEnableButton.Primary
        {...extraProps}
        class="Forward-Button"
        pendingText="Enabling..."
        onClick={this.onClickFeatureEnableSliderButton}
      >
        {buttonText || 'Get Started'}
      </FeatureEnableButton.Primary>
    );
  }
}

class NextButton extends React.PureComponent {
  onClickNext = (...args) => {
    const { props } = this;

    window.rzpAnalytics({
      eventCategory: `Product Introduction (${props.feature})`,
      eventAction: `Page ${props.page} - Next CTA`,
    });

    // eslint-disable-next-line
    props.onClick && props.onClick(args);
  };

  render() {
    return (
      <Button.Primary
        feature={this.props.feature}
        class="Forward-Button"
        iconAfter="arrow-forward"
        pendingText="Enabling..."
        onClick={this.onClickNext}
      >
        Get Started
      </Button.Primary>
    );
  }
}

@RTracking((props) => window.rzpQ.component(`${props.feature}_skip_and_get_started_btn`))
class SkipAndGetStartedButton extends React.PureComponent {
  onClickFeatureEnableButton = (...args) => {
    const { props } = this;

    window.rzpAnalytics({
      eventCategory: `Product Introduction (${props.feature})`,
      eventAction: `Page ${props.page} - Skip and Get Started`,
    });

    this.props.tracking.trackEvent(
      window.rzpQ.productOnboarding().initiated(`${props.feature}.onboarding.get_started`, {
        clickSource: `Screen_${props.active === 0 ? 1 : 2}_SkipAndGetStarted_CTA`,
      }),
    );

    // eslint-disable-next-line
    props.onClick && props.onClick(args);
  };

  render() {
    const { props } = this;

    return (
      <FeatureEnableButton.Transparent
        {...props}
        feature={props.feature}
        onClick={this.onClickFeatureEnableButton}
        isLocalEnabler={props.isLocalEnabler}
      >
        {props.isTour ? 'Skip' : 'Skip And Get Started'}
      </FeatureEnableButton.Transparent>
    );
  }
}

export { NextButton, SkipAndGetStartedButton, FeatureEnableSliderButton };
