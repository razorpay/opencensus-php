import React, { useEffect, useState } from 'react';
import OffersForYouIcon from './OffersForYouIcon';
import RazorpayXNitroAnnouncement, { nitroCampaignId } from 'common/ui/NotificationsDropdown/RazorpayXNitroAnnouncement';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { connect } from 'react-redux';
import { compose } from 'redux';
import RTracking from 'react-tracking';
import LocalStorageService from 'common/utils/localStorage';

const OffersForYou = ({ closeModal, openModal, tracking, user }) => {
  const offersForYouState = LocalStorageService.getItem('offers_for_you_state');
  let showAnimation = true;
  if (offersForYouState === 'animationShown' || offersForYouState === 'hasAppliedCA')
    showAnimation = false;
  const [isStopped, setIsStopped] = useState(!showAnimation);

  useEffect(() => {
    tracking.trackEvent(
      window.rzpQ.merchantActions().success('merchant_dashboard.display_offer_for_you', {
        ID: nitroCampaignId().version,
      }),
    );
    if (!(offersForYouState === 'animationShown' || offersForYouState === 'hasAppliedCA'))
      LocalStorageService.setItem('offers_for_you_state', 'animationShown');
  }, []);

  const handleClick = () => {
    openModal({
      component: <RazorpayXNitroAnnouncement hideModal={closeModal} fromWhere="offers-for-you" />,
      size: 'xlarge',
    });

    tracking.trackEvent(
      window.rzpQ.merchantActions().initiated('merchant_dashboard.click_offer_for_you', {
        ID: nitroCampaignId().version,
      }),
    );

    setIsStopped(true);
  };

  return (
    <li className='offers-for-you'>
      <a onClick={handleClick}>
        <OffersForYouIcon setIsStopped={setIsStopped} isStopped={isStopped}/>
        Exclusive Offer
      </a>
    </li>
  );
}

export default compose(
  RTracking({
    page: 'OffersForYou',
  }),
  connect(
    (state) => {
      return {
        user: state.session.user,
      };
    },
    {
      openModal,
      closeModal,
    },
  )
)(OffersForYou);
