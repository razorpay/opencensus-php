import React, { useEffect, useState } from 'react';
import OffersForYouIcon from './OffersForYouIcon';
import RazorpayXNitroAnnouncement, {
  getCampaignID,
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
import ExclusiveOffer from '../ExclusiveOffer/index';
import NitroSelfServe from '../../ui/NotificationsDropdown/Neostone/index';
import { withRouter } from 'react-router-dom';
import {
  setActivePageName as fnSetActivePageName,
  setBaseLocation as fnSetBaseLocation,
} from 'merchant/reducers/app';

// number of times to show MTU offer
const COUNT_TO_SHOW_MTU_OFFER = 5;

const OffersForYou = ({
  closeModals,
  openModals,
  tracking,
  canShowOnboardingOffers,
  user,
  mtuOfferCount,
  history,
  setActivePageName,
  setBaseLocation,
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
          ID: getCampaignID(),
          flow_type: user.isPartOfNeostone ? 'self_serve' : 'sales_led',
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

  const handleConnectedBankingFlow = () => {
    history.push('/connected-banking/icici-linked-ca');
    setBaseLocation('/connected-banking/icici-linked-ca');
    setActivePageName('Connected Banking');
  };

  const handleClick = () => {
    /* onboarding offer will be the priority over the other offers.
    if two offer enable at the same time */
    if (user.isICICILinkedCAEnabled) handleConnectedBankingFlow();
    else if (canShowOnboardingOffers) {
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
    } else if (user.isPartOfNeostone) {
      openModals({
        component: <NitroSelfServe user={user} handleClose={closeModals} tracking={tracking} />,
        size: 'xlarge',
        className: 'RazorpayXNitroAnnouncement--Modal',
      });
    } else if (user.isGSExclusiveOfferEnabled) {
      openModals({
        component: <ExclusiveOffer />,
        size: 'xlarge',
        className: 'GSExclusiveOffer--Modal',
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
        ID: getCampaignID(),
        flow_type: user.isPartOfNeostone ? 'self_serve' : 'sales_led',
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

export default withRouter(
  compose(
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
        setActivePageName: fnSetActivePageName,
        setBaseLocation: fnSetBaseLocation,
      },
    ),
  )(OffersForYou),
);
