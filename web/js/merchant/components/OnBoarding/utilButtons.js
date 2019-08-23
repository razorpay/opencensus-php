import Button from 'component/Button';

import FeatureEnableButton from './FeatureEnableButton';

export const FeatureEnableSliderButton = props => {
  const { isLocalEnabler, feature, onClick, page } = props;

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
      onClick={(...args) => {
        window.rzpAnalytics({
          eventCategory: `Product Introduction (${feature})`,
          eventAction: `Page ${page} - Get Started CTA`,
        });

        onClick && onClick(args);
      }}
    >
      Get Started
    </FeatureEnableButton.Primary>
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

      onClick && onClick(args);
    }}
  >
    Get Started
  </Button.Primary>
);

export const SkipAndGetStartedButton = ({
  feature,
  onClick,
  page,
  isTour,
  isLocalEnabler,
}) => (
  <FeatureEnableButton.Transparent
    onClick={(...args) => {
      window.rzpAnalytics({
        eventCategory: `Product Introduction (${feature})`,
        eventAction: `Page ${page} - Skip and Get Started`,
      });

      onClick && onClick(args);
    }}
    feature={feature}
    isLocalEnabler={isLocalEnabler}
  >
    {isTour ? 'Skip' : 'Skip And Get Started'}
  </FeatureEnableButton.Transparent>
);
