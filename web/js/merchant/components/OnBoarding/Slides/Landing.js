import RTracking from 'react-tracking';

import Button from 'common/new-ui/Button';

import DataList from 'merchant/components/DataList';

@RTracking(props =>
  window.rzpQ.component(`${props.feature}_onboarding_landing_page`)
)
export default class OnBoardingLanding extends React.PureComponent {
  componentDidMount() {
    this.props.tracking.trackEvent(
      window.rzpQ
        .productOnboarding()
        .success(`${this.props.feature}.onboarding.start`)
    );
  }

  handleNexButton = () => {
    return this.props.next(() => {
      this.props.tracking.trackEvent(
        window.rzpQ
          .productOnboarding()
          .success(`${this.props.feature}.onboarding.introduction_next`)
      );

      window.rzpAnalytics({
        eventCategory: `Onboarding Card (${this.props.feature})`,
        eventAction: `Page ${this.props.active} - Next CTA`,
      });
    });
  };

  render() {
    const { title, desc, pros, imageUrl } = this.props;

    return (
      <div
        class="OnBoarding--Slide OnBoarding--ImageSlide OnBoarding--Landing"
        key="LandingSlide"
      >
        <div class="Landing--Image">
          <img src={imageUrl} alt="landing-image" />
        </div>

        <div class="Product--Details">
          <div class="Details-heading">
            <span class="dash" /> Razorpay
          </div>

          <div class="Details-title">{title}</div>

          <div class="Details-desc">{desc}</div>

          {pros && <DataList horizontalDivider>{pros}</DataList>}

          <div class="Button-Container">
            <Button
              class="Forward-Button"
              iconAfter="arrow-forward"
              onClick={this.handleNexButton}
            >
              Next
            </Button>
          </div>
        </div>
      </div>
    );
  }
}
