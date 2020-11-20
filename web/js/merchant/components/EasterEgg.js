import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import useLocalStorage from 'merchant/utils/useLocalStorage';

const EasterEgg = (props) => {
  // set initial impressions
  const [impressions, setimpressions] = useLocalStorage('easter-egg-impressions', 0);

  useEffect(() => {
    // Only when easter egg is visible, track rendering
    if (impressions < 5) {
      props.tracking.trackEvent(
        window.rzpQ &&
          window.rzpQ.onbr().initiated(`merchant_dashboard.display_easteregg.success`, {
            id: `FTX2020`,
          }),
      );
    }
  }, []);

  const claimTicket = () => {
    window.open('https://razorpay.com/ftx/?coupon_id=FTXEGGHUNT#buy', '_blank');
    window.rzpAnalytics({
      eventCategory: `Easter egg Dashboard`,
      eventAction: 'Clicked on claim free ticket',
    });
  };

  const knowMore = () => {
    window.rzpAnalytics({
      eventCategory: `Easter egg Dashboard`,
      eventAction: 'Clicked on claim free ticket',
    });
  };

  const clickEasterEgg = () => {
    window.rzpAnalytics({
      eventCategory: `Easter egg Dashboard`,
      eventAction: 'Clicked on Easter egg',
    });

    props.tracking.trackEvent(
      window.rzpQ &&
        window.rzpQ.onbr().initiated(`merchant_dashboard.click_easteregg.success`, {
          id: `FTX2020`,
        }),
    );

    if (impressions) {
      setimpressions(impressions + 1);
    } else {
      setimpressions(1);
    }

    props.openModal({
      component: (
        <div class="ftx-popup">
          <i class="i i-close pop-close" onClick={props.closeModal} />
          <button class="btn" onClick={claimTicket}>
            CLAIM YOUR FREE FTX TICKETS
            <i class="i i-arrow-forward" />
          </button>
          <a
            href="https://razorpay.com/ftx/?coupon_id=FTXEGGHUNT"
            rel="noopener noreferrer"
            target="_blank"
            onClick={knowMore}
          >
            Know More
          </a>
        </div>
      ),
    });
  };

  return impressions >= 5 ? null : (
    <div class={`ftx-container ${props.extraClass}`} onClick={clickEasterEgg} />
  );
};

export default connect(null, { openModal, closeModal })(
  RTracking(() => window.rzpQ && window.rzpQ.component('EasteEgg'))(EasterEgg),
);
