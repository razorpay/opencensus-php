import React, { useEffect } from 'react';
import RTracking from 'react-tracking';
import Button from 'common/new-ui/Button';
import { RZPFeatures } from 'merchant/helpers/data';
import track from './track';
import { compose } from 'redux';

const PlatformCard = ({ id, icon, title, handlePlatformSelection }) => {
  const onSelectPlatform = () => {
    handlePlatformSelection(id);
  };

  return (
    <div className="platform" onClick={onSelectPlatform}>
      <img className="platform-icon" src={icon} />

      <div className="platform-content">
        <div className="platform-title">{title}</div>
        <i className="i i-aff-forward platform-title-icon" />
      </div>
    </div>
  );
};

const OnboardingPlatforms = (props) => {
  const { title, platforms } = props;

  useEffect(() => {
    track.websitePlatformRender(['shopify', 'woocomerce', 'native']);
  }, []);

  const handleBackButtonClick = () => {
    props.history.push(`/affordability/widget`);
  };

  const handlePlatformSelection = (id) => {
    track.platformSelect(id === 'others' ? 'native' : id);
    props.history.push(`/affordability/widget/setup/${id}`);
  };

  return (
    <div className="platforms-page">
      <div className="pp-header">
        <div className="pp-header-title">{title}</div>
      </div>

      <div className="platforms">
        {platforms?.map((data, idx) => (
          <PlatformCard {...data} key={idx} handlePlatformSelection={handlePlatformSelection} />
        ))}
      </div>
      <div className="Button-Container">
        <Button.Transparent
          className="Back-Button"
          iconBefore="arrow-back"
          onClick={handleBackButtonClick}
        >
          Back
        </Button.Transparent>
      </div>
    </div>
  );
};

export default compose(
  // eslint-disable-next-line babel/new-cap
  RTracking(() =>
    window.rzpQ.component(`${RZPFeatures.AFFORDABILITY_WIDGET}_onboarding_platforms_page`),
  )(OnboardingPlatforms),
);
