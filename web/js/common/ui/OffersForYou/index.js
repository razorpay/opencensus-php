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
import RTracking from 'react-tracking';
import LocalStorageService from 'common/utils/localStorage';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

const OffersForYou = ({ closeModals, openModals, tracking, canShowOnboardingOffers, user }) => {
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

  const handleClick = () => {
    if (user.isProjectMoonshineEnabled) {
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

      tracking.trackEvent(
        window.rzpQ.merchantActions().initiated('merchant_dashboard.click_offer_for_you', {
          ID: nitroCampaignId().version,
        }),
      );
    } else if (canShowOnboardingOffers) {
      openModals({
        component: <OnboardingCoupons closeModal={closeModals} />,
        size: 'xlarge',
      });
      LocalStorageService.setItem(`showed_popup--${window.rzp_user.current}`, 'visited');
      analyticsTrack({
        objectName: 'Exclusive Offer',
        actionName: 'clicked',
        screen: 'home page',
        properties: {
          location: 'top header',
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      });
    } else {
      openModals({
        component: (
          <RazorpayXNitroAnnouncement hideModal={closeModals} fromWhere="offers-for-you" />
        ),
        size: 'xlarge',
        className: 'RazorpayXNitroAnnouncement--Modal',
      });

      tracking.trackEvent(
        window.rzpQ.merchantActions().initiated('merchant_dashboard.click_offer_for_you', {
          ID: nitroCampaignId().version,
        }),
      );
    }

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
  RTracking(() => window.rzpQ.component('OffersForYou')),
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
