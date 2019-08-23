import Button from 'component/Button';

import FeatureEnableButton from './FeatureEnableButton';

export class FeatureEnableSliderButton extends React.PureComponent {
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

export class NextButton extends React.PureComponent {
  onClickNext = (...args) => {
    const { props } = this;

    window.rzpAnalytics({
      eventCategory: `Product Introduction (${props.feature})`,
      eventAction: `Page ${props.page} - Next CTA`,
    });

    props.onClick && props.onClick(args);
  };
  render() {
    return (
      <Button.Primary
        feature={props.feature}
        class="Forward-Button"
        iconAfter="arrow-forward"
        pendingText="Enabling..."
        onClick={onClickNext(props)}
      >
        Get Started
      </Button.Primary>
    );
  }
}

export class SkipAndGetStartedButton extends React.PureComponent {
  onClickFeatureEnableButton = (...args) => {
    const { props } = this;

    window.rzpAnalytics({
      eventCategory: `Product Introduction (${props.feature})`,
      eventAction: `Page ${props.page} - Skip and Get Started`,
    });

    props.onClick && props.onClick(args);
  };

  render() {
    return (
      <FeatureEnableButton.Transparent
        feature={props.feature}
        onClick={this.onClickFeatureEnableButton}
        isLocalEnabler={props.isLocalEnabler}
      >
        {props.isTour ? 'Skip' : 'Skip And Get Started'}
      </FeatureEnableButton.Transparent>
    );
  }
}
