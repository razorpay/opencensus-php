import Button from 'component/Button';

import FeatureEnableButton from './FeatureEnableButton';

class FeatureEnableSliderButton extends React.PureComponent {
  onClickFeatureEnableSliderButton = (...args) => {
    const { props } = this;

    window.rzpAnalytics({
      eventCategory: `Product Introduction (${props.feature})`,
      eventAction: `Page ${props.page} - Get Started CTA`,
    });

    props.onClick && props.onClick(args);
  };

  render() {
    const { isLocalEnabler, feature } = this.props;

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
        Get Started
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

    window.rzpQ
      .onbr()
      .success(`${props.feature}.onboarding.introduction_next.success`);

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

class SkipAndGetStartedButton extends React.PureComponent {
  onClickFeatureEnableButton = (...args) => {
    const { props } = this;

    window.rzpAnalytics({
      eventCategory: `Product Introduction (${props.feature})`,
      eventAction: `Page ${props.page} - Skip and Get Started`,
    });

    window.rzpQ
      .onbr()
      .initiated(`${props.feature}.onboarding.get_started.initiated`);

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
