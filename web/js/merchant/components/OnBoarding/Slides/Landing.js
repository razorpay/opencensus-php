import RTracking from 'react-tracking';

import Button from 'component/Button';

import DataList from 'merchant/components/DataList';

@RTracking(() => window.rzpQ.component('OnBoardingLanding'))
export default class OnBoardingLanding extends React.PureComponent {
  @RTracking(props => {
    return props.tracking.trackEvent(
      window.rzpQ
        .onbr()
        .initiated(`${props.feature}.onboarding.introduction_next`)
    );
  })
  handleNexButton = (...args) => {
    window.rzpAnalytics({
      eventCategory: `Onboarding Card (${this.props.feature})`,
      eventAction: `Page ${this.props.active} - Next CTA`,
    });

    window.rzpQ
      .onbr()
      .success(`${this.props.feature}.onboarding.introduction_next`);

    this.props.next(args);
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
