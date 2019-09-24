import Button, { AsyncBtn } from 'component/Button';

export default class OnBoardingFeatures extends React.PureComponent {
  handleBackButton = (...args) => {
    window.rzpAnalytics({
      eventCategory: `Onboarding Card (${this.props.feature})`,
      eventAction: `Page ${this.props.active} - Back CTA`,
    });

    this.props.prev(args);
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
                    <FeatureLink
                      key={idx}
                      {...data}
                      page={active}
                      feature={feature}
                    />
                    &bull;{' '}
                  </React.Fragment>
                );
              }

              return (
                <FeatureLink
                  key={idx}
                  {...data}
                  page={active}
                  feature={feature}
                />
              );
            })}
          </div>
        </div>

        <div class="Features">
          {features.map((data, idx) => <FeatureCard {...data} key={idx} />)}
        </div>

        <div class="Button-Container">
          <Button.Transparent
            iconBefore="arrow-back"
            onClick={this.handleBackButton}
          >
            Back
          </Button.Transparent>

          <NextButton />
        </div>
      </div>
    );
  }
}

const FeatureCard = ({ icon, title, desc }) => (
  <div class="Feature">
    <img class="Feature-icon" src={icon} />

    <div class="Feature-title">{title}</div>

    <p class="Feature-desc">{desc}</p>
  </div>
);

const FeatureLink = ({ ga, url, page, label, feature }) => (
  <a
    class="external-link"
    onClick={() => {
      window.rzpAnalytics({
        eventCategory: `Onboarding Card (${feature})`,
        eventAction: `Page ${page} - ${ga}`,
      });

      window.open(url);
    }}
  >
    {label}
  </a>
);
