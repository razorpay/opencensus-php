import React from 'react';
import RTracking from 'react-tracking';
import { getCustomURL } from 'merchant/components/DocsLink';

import Button from 'common/new-ui/Button';
import FeatureCard from 'merchant/components/Feature';
import track from '../track';
import ShowWhen from 'merchant/components/ShowWhen';
import { isOrgFeatureExist } from 'merchant/models/User';

@RTracking((props) => window.rzpQ.component(`${props.feature}_onboarding_feature_page`))
export default class OnBoardingFeatures extends React.PureComponent {
  handleBackButton = () => {
    this.props.prev(() => {
      window.rzpAnalytics?.({
        eventCategory: `Onboarding Card (${this.props.feature})`,
        eventAction: `Page ${this.props.active} - Back CTA`,
      });
      track.onFeatureBack(this.props.feature);
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
            <FeatureLink {...moreFeaturesLink} page={active} feature={feature} />
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

@RTracking((props) => window.rzpQ.component(`${props.feature}_onboarding_feature_page`))
class FeatureLink extends React.PureComponent {
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
