import React from 'react';
import Button from 'common/new-ui/Button';
import DataList from './DataList';
import RTracking from 'react-tracking';
import track from '../track';
import ShowWhen from 'merchant/components/ShowWhen';
@RTracking((props) => window.rzpQ.component(`${props.feature}_onboarding_landing_page`))
export default class OnBoardingLanding extends React.PureComponent {
  componentDidMount() {
    track.onBoardingSuccess(this.props.feature);
  }

  handleNexButton = () => {
    return this.props.next(() => {
      track.introductionNextSuccess(this.props.feature);
      window.rzpAnalytics?.({
        eventCategory: `Onboarding Card (${this.props.feature})`,
        eventAction: `Page ${this.props.active} - Next CTA`,
      });
    });
  };

  render() {
    const {
      title,
      desc,
      pros,
      heading,
      imageUrl,
      ytVideoUrl,
      callout,
      className = '',
    } = this.props;

    return (
      <div
        class={`OnBoarding--Slide OnBoarding--ImageSlide OnBoarding--Landing ${className}`}
        key="LandingSlide"
      >
        {ytVideoUrl && (
          <div class="Landing--Video">
            <iframe
              width="560"
              height="315"
              src={ytVideoUrl}
              title="YouTube video player"
              frameBorder="0"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
              allowFullScreen
            />
          </div>
        )}

        {imageUrl && (
          <ShowWhen additionalCondition={(user) => !user.isOrgAxis}>
            <div class="Landing--Image">
              <img src={imageUrl} alt="landing-image" />
            </div>
          </ShowWhen>
        )}

        <div class="Product--Details">
          <div class="Details-heading">
            <span class="dash" /> Razorpay
          </div>

          <div class="Details-title">{title}</div>

          <div class="Details-heading">{heading}</div>

          <div class="Details-desc">{desc}</div>

          {pros && <DataList horizontalDivider>{pros}</DataList>}

          <div class="callout">{callout}</div>

          <div class="Button-Container">
            <Button class="Forward-Button" iconAfter="arrow-forward" onClick={this.handleNexButton}>
              Read More
            </Button>
          </div>
        </div>
      </div>
    );
  }
}
