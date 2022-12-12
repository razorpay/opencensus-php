import React, { useState } from 'react';
import { connect } from 'react-redux';
import { AsyncBtn } from 'common/new-ui/Button';
import { getItem, setItem } from 'common/utils/localStorage';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import JoinWaitlistButton from './JoinedWaitlistButton';
import DocsLink from 'merchant/components/DocsLink';
function ComingSoon({ user }) {
  const [isJoinedWaitlist, setJoinedWaitlist] = useState(
    () => !!getItem(`affordability-widget-onboarding-banner-${user.current}`),
  );
  const [isPending, setPending] = useState(false);

  const joinTheWaitlist = () => {
    return new Promise((resolve) => {
      setTimeout(() => {
        setItem(`affordability-widget-onboarding-banner-${user.current}`, 1);

        analyticsTrack({
          objectName: 'Affordability Widget Early Access Interest',
          actionName: 'clicked',
          screen: 'Affordability Widget',
          properties: {
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
          toLumberjack: true,
        });
        resolve('Success');
      }, 1000);
    });
  };

  const handleJoinWaitlistClick = () => {
    setPending(true);
    joinTheWaitlist()
      .then((res) => {
        if (res === 'Success') {
          setJoinedWaitlist(true);
        }
      })
      .finally(() => {
        setPending(false);
      });
  };

  const handleReadMoreClick = () => {
    analyticsTrack({
      objectName: 'Affordability  Widget Early Access Read more',
      actionName: 'clicked',
      screen: 'Affordability Widget',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
      toLumberjack: true,
    });
  };

  return (
    <>
      <DocsLink
        url="https://razorpay.com/docs/payments/payment-gateway/affordability/widget"
        title="Read more"
        onClick={handleReadMoreClick}
      />
      {isJoinedWaitlist ? (
        <JoinWaitlistButton />
      ) : (
        <>
          <AsyncBtn.Primary
            class="Forward-Button"
            onClick={handleJoinWaitlistClick}
            showLoader={true}
          >
            Get Early access
            {isPending && <span className="spin-btn white visible" />}
          </AsyncBtn.Primary>
        </>
      )}
    </>
  );
}

export default connect((state) => ({ user: state.session.user }), null)(ComingSoon);
