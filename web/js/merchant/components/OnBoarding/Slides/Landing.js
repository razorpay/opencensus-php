import Button from 'component/Button';

import DataList from 'merchant/components/DataList';

export default class OnBoardingLanding extends React.PureComponent {
  render() {
    const { title, desc, pros, next, imageUrl, feature, active } = this.props;

    return (
      <div class="OnBoarding--Slide OnBoarding--ImageSlide OnBoarding--Landing">
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
              onClick={(...args) => {
                window.rzpAnalytics({
                  eventCategory: `Onboarding Card (${feature})`,
                  eventAction: `Page ${active} - Next CTA`,
                });

                next(args);
              }}
            >
              Next
            </Button>
          </div>
        </div>
      </div>
    );
  }
}
