import RTracking from 'react-tracking';

import Button from 'common/new-ui/Button';
import FeatureCard from 'merchant/components/Feature';

@RTracking((props) => window.rzpQ.component(`${props.feature}_onboarding_feature_page`))
export default class OnBoardingFeatures extends React.PureComponent {
  handleBackButton = () => {
    this.props.prev(() => {
      window.rzpAnalytics({
        eventCategory: `Onboarding Card (${this.props.feature})`,
        eventAction: `Page ${this.props.active} - Back CTA`,
      });

      this.props.tracking.trackEvent(
        window.rzpQ.productOnboarding().success(`${this.props.feature}.onboarding.features_back`),
      );
    });
  };

  render() {
    const {
      title,
      nextBtn: NextButton,
      prev,
      active,
      feature,
      features,
      featureLinks,
    } = this.props;

    return (
      <div class="OnBoarding--Slide OnBoarding--Features" key="FeatureSlide">
        <div class="Header">
          <div class="Header-title">{title}</div>

          <div class="Header-external-links">
            {featureLinks.map((data, idx) => {
              if (featureLinks.length - 1 !== idx) {
                return (
                  <React.Fragment>
                    <FeatureLink key={idx} {...data} page={active} feature={feature} />
                    &bull;{' '}
                  </React.Fragment>
                );
              }

              return <FeatureLink key={idx} {...data} page={active} feature={feature} />;
            })}
          </div>
        </div>

        <div class="Features">
          {features.map((data, idx) => (
            <FeatureCard {...data} key={idx} />
          ))}
        </div>

        <div class="Button-Container">
          <Button.Transparent iconBefore="arrow-back" onClick={this.handleBackButton}>
            Back
          </Button.Transparent>

          <NextButton />
        </div>
      </div>
    );
  }
}

@RTracking((props) => window.rzpQ.component(`${props.feature}_onboarding_feature_page`))
class FeatureLink extends React.PureComponent {
  handleFeatureLink = () => {
    const { ga, url, page, label, feature } = this.props;

    window.rzpAnalytics({
      eventCategory: `Onboarding Card (${feature})`,
      eventAction: `Page ${page} - ${ga}`,
    });

    this.props.tracking.trackEvent(
      window.rzpQ.productOnboarding().success(`${feature}.onboarding.features_hyperlink`),
    );

    this.props.onClick && this.props.onClick();

    window.open(url);
  };
  render() {
    return (
      <a class="external-link" onClick={this.handleFeatureLink}>
        {this.props.label}
      </a>
    );
  }
}
