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
import { getAssetTrackingProperties } from 'merchant/models/GrowthService/commonUtils';

// number of times to show MTU offer
const COUNT_TO_SHOW_MTU_OFFER = 5;

const OffersForYou = ({
  closeModals,
  openModals,
  loading,
  tracking,
  exclusive_offers,
  canShowOnboardingOffers,
  user,
  mtuOfferCount,
  history,
  setActivePageName,
  setBaseLocation,
  showMobileNav = false,
}) => {
  const offersForYouState = LocalStorageService.getItem('offers_for_you_state');
  let showAnimation = true;
  if (offersForYouState === 'animationShown' || offersForYouState === 'hasAppliedCA')
    showAnimation = false;
  const [isStopped, setIsStopped] = useState(!showAnimation);

  useEffect(() => {
    if (!canShowOnboardingOffers && !loading) {
      const eventName = 'merchant_dashboard.display_offer_for_you';
      const id = exclusive_offers?.id ? exclusive_offers.id : getCampaignID();
      tracking.trackEvent(
        window.rzpQ.merchantActions().success(eventName, {
          ID: id,
          flow_type: user.isPartOfNeostone ? 'self_serve' : 'sales_led',
          ...getAssetTrackingProperties(id, exclusive_offers.tracking_data, {}, eventName),
        }),
      );
    }
    if (!(offersForYouState === 'animationShown' || offersForYouState === 'hasAppliedCA'))
      LocalStorageService.setItem('offers_for_you_state', 'animationShown');
  }, [loading]);

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
    if (!showMobileNav) {
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
      } else if (!loading && exclusive_offers?.id) {
        openModals({
          component: <ExclusiveOffer />,
          size: 'xlarge',
          className: 'GSExclusiveOffer--Modal',
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
      } else if (
        user.isUCCapitalCardsOnlyCampaignEnabled ||
        user.isUCCapitalLOCOnlyCampaignEnabled ||
        user.isProjectKeystoneCorporateCardsEnabled ||
        user.isProjectKeystoneCashAdvanceEnabled ||
        user.isProjectNitroCorporateCard ||
        user.isProjectNitroEnabled ||
        user.isICICILinkedCAFlowEnabled('offers-for-you')
      ) {
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
    } else if (user.isProjectNitroEnabled) {
      history.push('/exclusive-offer/nitro');
    }

    tracking.trackEvent(
      window.rzpQ.merchantActions().initiated('merchant_dashboard.click_offer_for_you', {
        ID: exclusive_offers?.id ? exclusive_offers.id : getCampaignID(),
        flow_type: user.isPartOfNeostone ? 'self_serve' : 'sales_led',
      }),
    );
    setIsStopped(true);
  };

  return (
    <li className={!showMobileNav ? 'offers-for-you' : ''}>
      <a onClick={handleClick}>
        <OffersForYouIcon setIsStopped={setIsStopped} isStopped={isStopped} />
        {!showMobileNav ? 'Exclusive Offer' : null}
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
          ...state.growthService.exclusive_offers,
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
