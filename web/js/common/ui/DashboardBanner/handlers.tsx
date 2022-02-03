import React from 'react';
import store, { getUser } from '../../../merchant/store';
import {
  openModal as openModalProp,
  closeModal as closeModalProp,
} from '../../../merchant_common/reducers/modals';
import {
  setActivePageName as fnSetActivePageName,
  setBaseLocation as fnSetBaseLocation,
} from '../../../merchant/reducers/app';
import RazorpayXNitroAnnouncement from '../NotificationsDropdown/RazorpayXNitroAnnouncement';
import PLFeaturesModal from '../../../merchant/components/Announcements/CatalystCampaignBanner/PLFeaturesModal';
import { sendDataToSalesForce } from '../../utils/common-api';
import ExclusiveOffer from '../ExclusiveOffer';

const getClickHandler = (id = '') => {
  const user = getUser();
  const openModal = (payload) => store.dispatch(openModalProp(payload));
  const closeModal = (payload) => store.dispatch(closeModalProp(payload));
  const setActivePageName = (payload) => store.dispatch(fnSetActivePageName(payload));
  const setBaseLocation = (payload) => store.dispatch(fnSetBaseLocation(payload));

  const openRazorpayXNitroModal = () => {
    openModal({
      component: <RazorpayXNitroAnnouncement hideModal={closeModal} />,
      className: 'RazorpayXNitroAnnouncement--Modal',
    });
  };

  const openPLFeaturesModal = () => {
    openModal({
      component: <PLFeaturesModal closeModal={closeModal} />,
      className: 'PL_Catalyst_Banner--Modal',
    });
  };

  const sendPayloadToSalesforce = (data, userObj) => () => {
    sendDataToSalesForce(data, userObj);
  };

  const showGSExclusiveOfferModal = () => {
    openModal({
      component: <ExclusiveOffer />,
      className: 'GSExclusiveOffer--Modal',
    });
  };

  const handleConnectedBankingFlow = () => {
    setBaseLocation('/connected-banking/icici-linked-ca');
    setActivePageName('Connected Banking');
  };

  switch (id) {
    case 'OCT-NITRO-CARDOFFER':
    case 'OCT-NITRO-ICICIBranded':
      return openRazorpayXNitroModal;
    case 'JUL21-PG-PL-Cross-selling':
      return openPLFeaturesModal;
    case 'settlements-banner-JUL21-RXCC-ULTRA':
      return sendPayloadToSalesforce('ultra-campaign', user);
    case 'SEP21-ULTRALOC-BANNER':
      return sendPayloadToSalesforce('ultra-campaign-p2-cash-advance', user);
    case 'GS-Exclusive-Offer-modal':
      return showGSExclusiveOfferModal;
    case 'JAN22-ICICI-CONNECTEDBANKING-DB':
      return handleConnectedBankingFlow;
    default:
      return undefined;
  }
};

store.subscribe(getClickHandler);

export { getClickHandler };
