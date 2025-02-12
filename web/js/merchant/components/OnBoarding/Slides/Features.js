import React from 'react';
import rTracking from 'react-tracking';

import { getCustomURL } from 'merchant/components/DocsLink';
import FeatureCard from 'merchant/components/Feature';
import track from 'merchant/components/OnBoarding/track';
import ShowWhen from 'merchant/components/ShowWhen';

import Button from 'common/new-ui/Button';
import { isOrgFeatureExist } from 'merchant/models/User';
import { compose } from 'redux';

class FeatureLinkComponent extends React.PureComponent {
  handleFeatureLink = () => {
    const { ga, url, page, feature, onClick } = this.props;

    window.rzpAnalytics?.({
      eventCategory: `Onboarding Card (${feature})`,
      eventAction: `Page ${page} - ${ga}`,
    });
    track.introductionHyperlink(feature);
    if (onClick) onClick();
    window.open(getCustomURL(url));
  };

  render() {
    return (
      <a className="external-link" onClick={this.handleFeatureLink}>
        {this.props.label}
      </a>
    );
  }
}

const FeatureLink = compose(
  rTracking((props) => window.rzpQ.component(`${props.feature}_onboarding_feature_page`)),
)(FeatureLinkComponent);

class OnBoardingFeatures extends React.PureComponent {
  handleBackButton = () => {
    const { handleBackButton: handleBackButtonFromProps, prev, feature, active } = this.props;

    if (handleBackButtonFromProps) {
      return handleBackButtonFromProps();
    }

    return prev(() => {
      window.rzpAnalytics?.({
        eventCategory: `Onboarding Card (${feature})`,
        eventAction: `Page ${active} - Back CTA`,
      });
      track.onFeatureBack(feature);
    });
  };

  render() {
    const {
      title,
      nextBtn,
      active,
      feature,
      features,
      featureLinks,
      moreFeaturesLink,
      handleMoreFeaturesLink,
    } = this.props;

    return (
      <div className="OnBoarding--Slide OnBoarding--Features" key="FeatureSlide">
        <div className="Header">
          <div className="Header-title">{title}</div>
          <ShowWhen additionalCondition={() => !isOrgFeatureExist('hide_razorpay_text_link')}>
            <div className="Header-external-links">
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
          </ShowWhen>
        </div>

        <div className="Features">
          {features.map((data, idx) => (
            <FeatureCard {...data} key={idx} />
          ))}
        </div>
        {moreFeaturesLink && (
          <div className="More-features-link flex">
            <FeatureLink
              {...moreFeaturesLink}
              page={active}
              feature={feature}
              onClick={handleMoreFeaturesLink}
            />
          </div>
        )}
        <div className="Button-Container">
          <Button.Transparent iconBefore="arrow-back" onClick={this.handleBackButton}>
            Back
          </Button.Transparent>

          {typeof nextBtn === 'function' ? nextBtn() : nextBtn}
          {/* {NextButton && <NextButton />} */}
        </div>
      </div>
    );
  }
}

export default compose(
  rTracking((props) => window.rzpQ.component(`${props.feature}_onboarding_feature_page`)),
)(OnBoardingFeatures);
