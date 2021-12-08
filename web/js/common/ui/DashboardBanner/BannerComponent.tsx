import React from 'react';
import { BannerComponentProps } from './TypesDeclare/BannerComponentTypes';
import AnnouncementBanner from '../../../merchant/components/Announcements/AnnouncementBanner';
import { connect } from 'react-redux';
import { compose } from 'redux';
import rTracking from 'react-tracking';
import BannerCTA from './BannerCTA';
import { externalURLTest } from './data';
import { getAssetTrackingProperties } from '../../../merchant/models/GrowthService/commonUtils';

const Description = ({ content }) => {
  const { description, type } = content;

  switch (type) {
    case 'bold':
      return <strong>{description}</strong>;
    case 'italics':
      return <i>{description}</i>;
    case 'normal':
    default:
      return <span>{description}</span>;
  }
};

const BannerComponent = ({
  id,
  title,
  theme,
  dismissible,
  ctaArray,
  user,
  content,
  text_link,
  tracking,
  fromWhere,
  tracking_data,
}: BannerComponentProps): React.ReactElement => {
  const trackingData = {
    title,
    banner_text: content?.description,
    card_id: id,
    source: fromWhere,
    ...getAssetTrackingProperties(id, tracking_data),
  };

  const trackCTAClick = (ctaIndex, label, url) => {
    tracking.trackEvent(
      window.rzpQ &&
        window.rzpQ.merchantActions().initiated(`merchant_dashboard.click_banner_cta${ctaIndex}`, {
          cta_value: label,
          link_url: url,
          ...trackingData,
        }),
    );
  };

  const CTAs = (
    <div className="banner-cta-group">
      {ctaArray?.map(({ clickHandler, url, label, isExternal, type }, index) => {
        const handleCTAClick = () => {
          trackCTAClick(index + 1, label, url);
          if (typeof clickHandler === 'function') clickHandler();
        };

        return (
          <BannerCTA
            key={`${label}-${index}-${url}`}
            clickHandler={handleCTAClick}
            url={url}
            isExternal={isExternal}
            type={type}
            label={label}
          />
        );
      })}
    </div>
  );

  let TextLink: React.ReactElement | null = null;
  if (text_link) {
    let isExternal = true;
    if (typeof text_link.url === 'string') isExternal = externalURLTest.test(text_link.url);

    TextLink = <BannerCTA {...text_link} isExternal={isExternal} type="transparent" />;
  }

  return (
    <AnnouncementBanner
      title={title}
      canBeClosed={dismissible}
      theme={theme}
      bannerKey={`${id}-${user.current}`}
      card_id={id}
      className="growth-service-banner"
      trackingData={trackingData}
    >
      <div className="banner-description-group">
        <Description content={content} />
        {text_link ? <span className="big-dot-separator" /> : null}
        {TextLink}
      </div>
      {CTAs}
    </AnnouncementBanner>
  );
};

export default compose<any>(
  rTracking({
    page: 'DashboardBanner',
  }),
  connect(
    (state) => ({
      user: state.session.user,
    }),
    null,
  ),
)(BannerComponent);
