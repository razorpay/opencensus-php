import React, { Suspense, useEffect, useState } from 'react';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';
import { compose } from 'redux';
import { AnnouncementIcon } from '@razorpay/blade/components';

import Loader from 'common/ui/Loader';
import { trackLoad } from 'common/ui/NotificationsDropdown/ga';
import { classList, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import lazy from 'merchant/routes/LazyLoader';
import { pushSlider as fnPushSlider } from 'merchant_common/reducers/multiSlider';

import { getExperimentVersion, getNotificationsReadData } from './common';

import './Icon.styl';
import { analyticsTrack } from 'common/utils/analytics';

const WhatsNewLazyComponent = lazy(() =>
  import(/* webpackChunkName: "WhatsNewLazyComponent" */ 'common/ui/WhatsNew'),
);

const WhatsNewIcon = ({ user, showMobileNav, tracking, pushSlider, isRTUXHomepage }) => {
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
        }),
    );

    setUnreadMsgs();
  }, []);

  const handleSliderToggleClick = () => {
    isRTUXHomepage &&
      analyticsTrack({
        screen: 'home page',
        objectName: 'Announcements Icon',
        actionName: 'clicked',
        properties: {
          version: 'v2',
          ...getCommonAnalyticsProperties(window.rzp_user, { addUserProperties: true }),
        },
      });
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
    if (isRTUXHomepage) {
      return (
        <AnnouncementIcon
          size="medium"
          onClick={handleSliderToggleClick}
          color="surface.text.subtle.lowContrast"
        />
      );
    }

    const { totalUnread } = getNotificationsReadData(user.current);
    const hasUnread = !!totalUnread;

    if (!showMobileNav) {
      return (
        <>
          <span onClick={handleSliderToggleClick} class={classList(hasUnread && 'highlight')}>
            What's New
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
  connect((state) => ({ user: state.session.user }), { pushSlider: fnPushSlider }),
)(WhatsNewIcon);
