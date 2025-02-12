import React from 'react';
import { getMode, getUser } from 'merchant/store';
import { compose } from 'redux';
import { connect } from 'react-redux';
import PropTypes from 'prop-types';
import AnnouncementBanner from '../../../merchant/components/Announcements/AnnouncementBanner';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';

function _track(source, merchant_id) {
  const mode = getMode();
  const { bannerText, bannerId, cta2Text, cta2Link, cta1Text, productName } = source;

  function onClickCTA1() {
    window.rzpQ.push(
      window.rzpQ.merchantActions().initiated('merchant_dashboard.click_banner_cta1', {
        mode,
        banner_text: bannerText,
        trackingID: bannerId,
        cta_value: cta1Text,
        source: productName,
        merchant_id,
      }),
    );
  }

  function onClickCTA2() {
    window.rzpQ.push(
      window.rzpQ.merchantActions().initiated('merchant_dashboard.click_banner_cta2', {
        mode,
        banner_text: bannerText,
        trackingID: bannerId,
        cta_value: cta2Text,
        link_url: cta2Link,
        source: productName,
        merchant_id,
      }),
    );
  }

  return {
    onClickCTA1,
    onClickCTA2,
  };
}

const BaseDashboardBanner = React.memo(
  ({
    title,
    bannerText,
    bannerId,
    cta1Text,
    cta1Link,
    targetForCta1,
    cta2Text,
    cta2Link,
    productName,
    cta1ClickHandler,
    canBeClosed = false,
  }) => {
    const user = getUser();
    const track = _track(
      {
        bannerText,
        bannerId,
        cta2Text,
        cta2Link,
        cta1Text,
        productName,
      },
      user.current,
    );

    const handleCTA1Click = () => {
      track.onClickCTA1();
      cta1ClickHandler();
    };
    const renderATag = cta1Link ? (
      <a
        className="Button--secondary Button scheduled-btn-act btn-border"
        href={cta1Link}
        target={targetForCta1}
        rel="noreferrer noopener"
      >
        {cta1Text}
      </a>
    ) : (
      <a
        className="Button--secondary Button scheduled-btn-act btn-border"
        onClick={handleCTA1Click}
        target={targetForCta1}
        rel="noreferrer noopener"
      >
        {cta1Text}
      </a>
    );

    return (
      <ErrorBoundary>
        <AnnouncementBanner
          title={title}
          canBeClosed={canBeClosed}
          theme="primary"
          bannerKey={`${bannerId}-${user.current}`}
          card_id={bannerId}
        >
          <span className="display-inline">{bannerText}</span>
          {renderATag}
          {cta2Link ? (
            <a
              href={cta2Link}
              className="Button--primary Button scheduled-btn-act btn-border"
              target="_blank"
              rel="noreferrer noopener"
            >
              {cta2Text}
            </a>
          ) : null}
        </AnnouncementBanner>
      </ErrorBoundary>
    );
  },
);

BaseDashboardBanner.prototype = {
  title: PropTypes.string,
  bannerText: PropTypes.string,
  bannerId: PropTypes.string,
  cta1Text: PropTypes.string,
  cta1Link: PropTypes.string,
  targetForCta1: PropTypes.string,
  cta2Text: PropTypes.string,
  cta2Link: PropTypes.string,
  productName: PropTypes.string,
  cta1ClickHandler: PropTypes.func,
  canBeClosed: PropTypes.bool,
};

BaseDashboardBanner.defaultProps = {
  title: '',
  bannerText: '',
  bannerId: '',
  cta1Text: '',
  cta1Link: '',
  targetForCta1: '_self',
  cta2Text: '',
  cta2Link: '',
  productName: '',
  cta1ClickHandler: () => {},
  canBeClosed: false,
};
export default compose(connect(null))(BaseDashboardBanner);
