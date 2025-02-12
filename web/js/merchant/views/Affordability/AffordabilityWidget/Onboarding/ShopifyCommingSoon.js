import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { AsyncBtn } from 'common/new-ui/Button';
import { getItem, setItem } from 'common/utils/localStorage';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import JoinWaitlistButton from './JoinedWaitlistButton';
import { platformIdMapping, platformTitleMapping } from './data';

function ShopifyCommingSoonCTA({ user, platform }) {
  const [isJoinedWaitlist, setJoinedWaitlist] = useState(
    () => !!getItem(`affordability-widget-${platform}-join-wishlist-${user.current}`),
  );
  const [isPending, setPending] = useState(false);

  const [content, setContent] = useState({
    title: platform == platformIdMapping.shopify ? 'Coming soon' : 'Join the waitlist',
    desc: 'Get early access by joining the waitlist.',
  });
  const platformName = platformTitleMapping[platform];

  useEffect(() => {
    if (isJoinedWaitlist) {
      setContent({
        title: 'Coming soon',
        desc:
          'Thank you for joining our waitlist. We have recorded your interest and will be in touch soon!',
      });
    }
  }, [isJoinedWaitlist]);
  const joinTheWaitlist = () => {
    return new Promise((resolve) => {
      setTimeout(() => {
        setItem(`affordability-widget-${platform}-join-wishlist-${user.current}`, 1);

        analyticsTrack({
          objectName: `Affordability Widget ${platformName} Plugin Early Access Interest`,
          actionName: 'clicked',
          screen: `Affordability Widget ${platformName} Setup`,
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

  return (
    <div className="step">
      <div className="step-content">
        <b className="title">{content.title}</b>
        <p className="description">{content.desc}</p>
        {isJoinedWaitlist ? (
          <JoinWaitlistButton platform={platformName} />
        ) : (
          <AsyncBtn.Primary
            className="Button--primary btn-lg btn-primary"
            onClick={handleJoinWaitlistClick}
            showLoader={true}
          >
            Get Early access
            {isPending && <span className="spin-btn white visible" />}
          </AsyncBtn.Primary>
        )}
      </div>
    </div>
  );
}

export default connect((state) => ({ user: state.session.user }), null)(ShopifyCommingSoonCTA);
