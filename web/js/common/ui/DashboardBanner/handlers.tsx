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
import { sendDataToSalesForce } from '../../utils/common-api';
import CatalystCampaign from '../GrowthCustomizeModal/CatalystCampaign';
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

  const handlePaymentCatalystCta1 = (imageID = '') => {
    openModal({
      component: <CatalystCampaign imageID={imageID} />,
      className: 'PP_cross_sell_Catalyst_Banner',
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
    case 'settlements-banner-JUL21-RXCC-ULTRA':
      return sendPayloadToSalesforce('ultra-campaign', user);
    case 'SEP21-ULTRALOC-BANNER':
      return sendPayloadToSalesforce('ultra-campaign-p2-cash-advance', user);
    case 'GS-Exclusive-Offer-modal':
      return showGSExclusiveOfferModal;
    case 'JAN22-ICICI-CONNECTEDBANKING-DB':
      return handleConnectedBankingFlow;
    case 'JAN22-CATALYST-PP-EL-CTA1':
    case 'JAN22-CATALYST-PP-ED-CTA1':
    case 'JAN22-CATALYST-PP-EC-CTA1':
      return () => handlePaymentCatalystCta1(id);
    default:
      return undefined;
  }
};

store.subscribe(getClickHandler);

export { getClickHandler };
