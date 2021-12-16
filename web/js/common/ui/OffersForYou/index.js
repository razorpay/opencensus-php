import React, { useEffect, useState } from 'react';
import OffersForYouIcon from './OffersForYouIcon';
import RazorpayXNitroAnnouncement, {
  nitroCampaignId,
} from 'common/ui/NotificationsDropdown/RazorpayXNitroAnnouncement';
import OnboardingCoupons from 'common/ui/OnboardingCoupons';
import RXPayrollMoonshineModal from 'common/ui/RXPayrollMoonshineModal';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { connect } from 'react-redux';
import { compose } from 'redux';
import rTracking from 'react-tracking';
import * as LocalStorageService from 'common/utils/localStorage';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

// number of times to show MTU offer
const COUNT_TO_SHOW_MTU_OFFER = 5;

const OffersForYou = ({
  closeModals,
  openModals,
  tracking,
  canShowOnboardingOffers,
  user,
  mtuOfferCount,
}) => {
  const offersForYouState = LocalStorageService.getItem('offers_for_you_state');
  let showAnimation = true;
  if (offersForYouState === 'animationShown' || offersForYouState === 'hasAppliedCA')
    showAnimation = false;
  const [isStopped, setIsStopped] = useState(!showAnimation);

  useEffect(() => {
    if (!canShowOnboardingOffers) {
      tracking.trackEvent(
        window.rzpQ.merchantActions().success('merchant_dashboard.display_offer_for_you', {
          ID: nitroCampaignId().version,
        }),
      );
    }
    if (!(offersForYouState === 'animationShown' || offersForYouState === 'hasAppliedCA'))
      LocalStorageService.setItem('offers_for_you_state', 'animationShown');
  }, []);

  const showMTUOffer = (isButtonClicked = false) => {
    openModals({
      component: (
        <OnboardingCoupons
          closeModal={closeModals}
          mtuCouponCount={mtuOfferCount}
          autoOpenOnboardingCoupon={user.autoOpenOnboardingCoupon}
          isButtonClicked={isButtonClicked}
        />
      ),
      size: 'xlarge',
    });
  };

  useEffect(() => {
    //show MTU offer 5 times on each new session
    const prevSessionID = LocalStorageService.getItem(`prev_session`);
    if (
      canShowOnboardingOffers &&
      window.session_id !== prevSessionID &&
      typeof mtuOfferCount === 'number' &&
      mtuOfferCount < COUNT_TO_SHOW_MTU_OFFER &&
      user.autoOpenOnboardingCoupon
    ) {
      showMTUOffer();
      LocalStorageService.setItem('prev_session', window.session_id);
    }
  }, [mtuOfferCount]);

  const handleClick = () => {
    /* onboarding offer will be the priority over the other offers.
    if two offer enable at the same time */
    if (canShowOnboardingOffers) {
      showMTUOffer(true);
      analyticsTrack({
        objectName: 'Exclusive Offer',
        actionName: 'clicked',
        screen: 'home page',
        properties: {
          location: 'top header',
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      });
    } else if (user.isProjectMoonshineEnabled) {
      openModals({
        component: (
          <RXPayrollMoonshineModal
            hideModal={closeModals}
            fromWhere="offers-for-you"
            tracking={tracking}
          />
        ),
        size: 'xlarge',
        className: 'RXPayrollMoonshine--Modal',
      });
    } else {
      openModals({
        component: (
          <RazorpayXNitroAnnouncement hideModal={closeModals} fromWhere="offers-for-you" />
        ),
        size: 'xlarge',
        className:
          user.isProjectKeystoneCorporateCardsEnabled || user.isProjectKeystoneCashAdvanceEnabled
            ? 'Keystone--Modal'
            : 'RazorpayXNitroAnnouncement--Modal',
      });
    }

    tracking.trackEvent(
      window.rzpQ.merchantActions().initiated('merchant_dashboard.click_offer_for_you', {
        ID: nitroCampaignId().version,
      }),
    );
    setIsStopped(true);
  };

  return (
    <li className="offers-for-you">
      <a onClick={handleClick}>
        <OffersForYouIcon setIsStopped={setIsStopped} isStopped={isStopped} />
        Exclusive Offer
      </a>
    </li>
  );
};

export default compose(
  rTracking(() => window.rzpQ.component('OffersForYou')),
  connect(
    (state) => {
      return {
        user: state.session.user,
      };
    },
    {
      openModals: openModal,
      closeModals: closeModal,
    },
  ),
)(OffersForYou);
