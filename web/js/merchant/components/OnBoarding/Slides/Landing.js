import React from 'react';
import Button from 'common/new-ui/Button';
import DataList from './DataList';
import RTracking from 'react-tracking';
import track from '../track';
import ShowWhen from 'merchant/components/ShowWhen';
import { isOrgFeatureExist } from 'merchant/models/User';
@RTracking((props) => window.rzpQ.component(`${props.feature}_onboarding_landing_page`))
export default class OnBoardingLanding extends React.PureComponent {
  componentDidMount() {
    const { includeKycProperties, feature } = this.props;
    track.onBoardingSuccess(feature, { includeKycProperties });
  }

  handleNexButton = () => {
    return this.props.next(() => {
      const { includeKycProperties, feature, active } = this.props;
      track.introductionNextSuccess(feature, null, { includeKycProperties });
      window.rzpAnalytics?.({
        eventCategory: `Onboarding Card (${feature})`,
        eventAction: `Page ${active} - Next CTA`,
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
      ctaText,
      className = '',
    } = this.props;
    const hideRzpTextLink = isOrgFeatureExist('hide_razorpay_text_link');
    return (
      <div
        className={`OnBoarding--Slide OnBoarding--ImageSlide OnBoarding--Landing ${className}`}
        key="LandingSlide"
      >
        {ytVideoUrl && (
          <div className="Landing--Video">
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
            <div className="Landing--Image">
              <img src={imageUrl} alt="landing-image" />
            </div>
          </ShowWhen>
        )}

        <div className="Product--Details">
          <ShowWhen additionalCondition={() => !hideRzpTextLink}>
            <div className="Details-heading">
              <span className="dash" /> Razorpay
            </div>
          </ShowWhen>
          <div className="Details-title">{title}</div>

          <div className="Details-heading">{heading}</div>
          <ShowWhen additionalCondition={() => !hideRzpTextLink}>
            <div className="Details-desc">{desc}</div>
          </ShowWhen>
          {pros && <DataList horizontalDivider>{pros}</DataList>}

          <div className="callout">{callout}</div>

          <div className="Button-Container">
            <Button
              type="button"
              className="Forward-Button"
              iconAfter="arrow-forward"
              onClick={this.handleNexButton}
            >
              {ctaText || 'Read More'}
            </Button>
          </div>
        </div>
      </div>
    );
  }
}
