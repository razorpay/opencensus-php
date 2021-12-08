import React from 'react';
import store, { getUser } from '../../../merchant/store';
import {
  openModal as openModalProp,
  closeModal as closeModalProp,
} from '../../../merchant_common/reducers/modals';
import RazorpayXNitroAnnouncement from '../NotificationsDropdown/RazorpayXNitroAnnouncement';
import PLFeaturesModal from '../../../merchant/components/Announcements/CatalystCampaignBanner/PLFeaturesModal';
import { sendDataToSalesForce } from '../../utils/common-api';

const getClickHandler = (id = '') => {
  const user = getUser();
  const openModal = (payload) => store.dispatch(openModalProp(payload));
  const closeModal = (payload) => store.dispatch(closeModalProp(payload));

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
    default:
      return undefined;
  }
};

store.subscribe(getClickHandler);

export { getClickHandler };
