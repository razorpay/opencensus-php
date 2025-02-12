import React from 'react';
import Button from 'common/new-ui/Button';
import DataList from './DataList';
import rTracking from 'react-tracking';
import track from 'merchant/components/OnBoarding/track';
import ShowWhen from 'merchant/components/ShowWhen';
import { isOrgFeatureExist } from 'merchant/models/User';
import { compose } from 'redux';

class OnBoardingLanding extends React.PureComponent {
  componentDidMount() {
    const { includeKycProperties, feature } = this.props;
    track.onBoardingSuccess(feature, { includeKycProperties });
  }

  handleNexButton = () => {
    const { includeKycProperties, feature, active, readMoreClicked } = this.props;
    if (readMoreClicked) {
      readMoreClicked();
    }
    return this.props.next(() => {
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
      businessName = 'Razorpay',
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
              <span className="dash" /> {businessName}
            </div>
          </ShowWhen>

          <div className="Details-title">{title}</div>

          {heading && <div className="Details-heading">{heading}</div>}

          <ShowWhen additionalCondition={() => !hideRzpTextLink}>
            <div className="Details-desc">{typeof desc === 'function' ? desc() : desc}</div>
          </ShowWhen>

          {pros && <DataList horizontalDivider>{pros}</DataList>}

          {callout && <div className="callout">{callout}</div>}

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

export default compose(
  rTracking((props) => window.rzpQ.component(`${props.feature}_onboarding_landing_page`)),
)(OnBoardingLanding);
