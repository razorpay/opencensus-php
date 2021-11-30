import React, { Suspense, useEffect, useState } from 'react';
import lazy from 'merchant/routes/LazyLoader';
import Loader from 'common/ui/Loader';
import { pushSlider } from 'merchant_common/reducers/multiSlider';
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

const WhatsNewIcon = ({ user, showMobileNav, tracking }) => {
  const [isOpen, setOpen] = useState(false);

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
          growth_service: user.isGrowthServiceEnabled,
        }),
      );
    }
  };

  useEffect(() => {
    tracking.trackEvent(
      window.rzpQ &&
        window.rzpQ.merchantActions().success('merchant_dashboard.display_notification', {
          experimentVersion: getExperimentVersion(user),
          lazy: true,
          growth_service: user.isGrowthServiceEnabled,
        }),
    );

    setUnreadMsgs();
  }, []);

  const handleSliderToggleClick = () => {
    pushSlider({
      component: (
        <Suspense fallback={<Loader />}>
          <WhatsNewLazyComponent />
        </Suspense>
      ),
      onClose: () => {
        setOpen(false);
      },
    });
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
          {hasUnread && <span className="bubble">{totalUnread}</span>}
        </>
      );
    }

    return (
      <>
        <i className="i i-horn" onClick={handleSliderToggleClick} />
        {hasUnread && <span className="new-bubble">{totalUnread}</span>}
      </>
    );
  };

  return (
    <div className={classList('whats-new-icon', isOpen && 'whats-new-icon--active')}>
      <div className="whats-new-icon-text">{getAnnouncementCta()}</div>
    </div>
  );
};

export default compose(
  // eslint-disable-next-line babel/new-cap
  RTracking(() => window.rzpQ.component('WhatsNewIcon')),
  connect((state) => ({ user: state.session.user }), { pushSlider }),
)(WhatsNewIcon);
