import React, { Suspense, useEffect, useState } from 'react';
import lazy from 'merchant/routes/LazyLoader';
import Loader from 'common/ui/Loader';
import Slider from 'common/ui/Slider';
import { openSlider, closeSlider } from 'merchant_common/reducers/slider';
import { connect } from 'react-redux';
import { compose } from 'redux';
import { classList } from 'common/utils/rzp-utils';
import { getExperimentVersion, getNotificationsReadData } from './common';
import { trackLoad } from '../NotificationsDropdown/ga';
import RTracking from 'react-tracking';
import './Icon.styl';

const WhatsNewLazyComponent = lazy(() =>
  import(/* webpackChunkName: "WhatsNewLazyComponent" */ 'common/ui/WhatsNew'),
);

const WhatsNewIcon = ({ user, open, showMobileNav, close, tracking }) => {
  const [isOpen, setOpen] = useState(false);

  const handleDocumentClick = (event) => {
    const target = event.target;
    const sliderContent = document.querySelector('.content-wrapper.whats-new');
    const sliderToggle = document.querySelector('.whats-new-slide-toggle');
    const whatsNewTooltip = document.querySelector('.whats-new__tooltip');
    const announcementDetails = document.querySelector(
      '.panel.panel-default.SliderPanel.announcement-details__container',
    );
    if (
      (sliderContent && sliderContent.contains(target)) ||
      (sliderToggle && sliderToggle.contains(target)) ||
      (whatsNewTooltip && whatsNewTooltip.contains(target)) ||
      (announcementDetails && announcementDetails.contains(target))
    ) {
      return;
    }
    setOpen(false);
  };

  const setUnreadMsgs = () => {
    const { totalUnread, ID, readID, unreadID } = getNotificationsReadData(user.current);

    trackLoad(totalUnread);

    if (totalUnread && window.rzpQ && window.rzpQ.merchantActions) {
      tracking.trackEvent(
        window.rzpQ.merchantActions().success('display.notification.bubble', {
          ID,
          readID,
          unreadID,
          experimentVersion: getExperimentVersion(user),
          lazy: true,
        }),
      );
    }
  };

  useEffect(() => {
    document.addEventListener('click', handleDocumentClick, true);
    setUnreadMsgs();

    return () => document.removeEventListener('click', handleDocumentClick, true);
  }, []);

  const handleSliderToggleClick = () => {
    open();
    setOpen(true);
  };

  const getAnnouncementCta = () => {
    const { totalUnread } = getNotificationsReadData(user.current);
    const hasUnread = !!totalUnread;

    if ((user.isAnnouncementTextEnabled || user.isWhatsNewTextEnabled) && !showMobileNav) {
      return (
        <>
          <span onClick={handleSliderToggleClick} class={classList(hasUnread && 'highlight')}>
            {user.isAnnouncementTextEnabled ? 'Announcements' : "What's New"}
          </span>
          {hasUnread && <span class="bubble">{totalUnread}</span>}
        </>
      );
    }

    return (
      <>
        <i className="i i-horn" onClick={handleSliderToggleClick} />
        {hasUnread && <span class="new-bubble">{totalUnread}</span>}
      </>
    );
  };

  return (
    <div className={classList('whats-new-icon', isOpen && 'whats-new-icon--active')}>
      <div className="whats-new-icon-text">{getAnnouncementCta()}</div>
      {isOpen ? (
        <Slider>
          <Suspense fallback={<Loader />}>
            <WhatsNewLazyComponent />
          </Suspense>
        </Slider>
      ) : null}
    </div>
  );
};

export default compose(
  connect((state) => ({ user: state.session.user }), { open: openSlider, close: closeSlider }),
  RTracking(() => window.rzpQ.component('WhatsNewIcon')),
)(WhatsNewIcon);
