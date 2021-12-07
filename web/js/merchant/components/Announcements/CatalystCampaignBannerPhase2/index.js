import React from 'react';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import { getMode, getUser } from 'merchant/store';
import { compose } from 'redux';
import { connect } from 'react-redux';
import { openModal } from 'merchant_common/reducers/modals';
import { Link } from 'react-router-dom';
import PLFeaturesModal from './PLFeaturesModal';

function _track({ source, merchant_id, bannerText, cardId, cta2Text, cta2Link, cta1Text }) {
  const mode = getMode();

  function onClickCTA1() {
    window.rzpQ.push(
      window.rzpQ.merchantActions().initiated('merchant_dashboard.click_banner_cta1', {
        mode,
        banner_text: bannerText,
        card_id: cardId,
        cta_value: cta1Text,
        source,
        merchant_id,
      }),
    );
  }

  function onClickCTA2() {
    window.rzpQ.push(
      window.rzpQ.merchantActions().initiated('merchant_dashboard.click_banner_cta2', {
        mode,
        banner_text: bannerText,
        card_id: cardId,
        cta_value: cta2Text,
        link_url: cta2Link,
        source,
        merchant_id,
      }),
    );
  }

  return {
    onClickCTA1,
    onClickCTA2,
  };
}

const CatalystCampaignBannerPhase2 = React.memo(
  ({
    productName,
    type,
    openModalBanner,
    title,
    bannerText,
    cardId,
    cta1Text,
    cta2Text,
    cta2Link,
  }) => {
    const user = getUser();
    const track = _track({
      source: productName,
      merchant_id: user.current,
      bannerText,
      cardId,
      cta2Text,
      cta2Link,
      cta1Text,
    });

    const handleCTA1Click = () => {
      openModalBanner({
        component: <PLFeaturesModal type={type} />,
        className: 'PL_Catalyst_Banner--Modal',
      });
      track.onClickCTA1();
    };

    return (
      <AnnouncementBanner
        title={title}
        canBeClosed={true}
        theme="primary"
        bannerKey={`catalyst-banner-${user.current}`}
        card_id={cardId}
      >
        <span class="display-inline banner-text-width">{bannerText}</span>
        <a class="Button--secondary Button scheduled-btn-act btn-border" onClick={handleCTA1Click}>
          {cta1Text}
        </a>
        <Link
          to={cta2Link}
          class="Button--primary Button scheduled-btn-act btn-border"
          onClick={track.onClickCTA2}
        >
          {cta2Text}
        </Link>
      </AnnouncementBanner>
    );
  },
);

export default compose(connect(null, { openModalBanner: openModal }))(CatalystCampaignBannerPhase2);
