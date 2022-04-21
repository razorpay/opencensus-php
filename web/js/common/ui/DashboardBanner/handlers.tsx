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
import ThankYouModal from 'merchant/components/Home/ThankYouModal';
import GrowthServiceModal from '../GrowthServiceModal';
import GrowthServiceCenterCTAModal from '../GrowthServiceModal/CenterCTAModal';

const getClickHandler = (id = '', type = '', variant = '') => {
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

  const showGSModal = () => {
    openModal({
      component: <GrowthServiceModal template_id={id} />,
      className: 'GS--Modal',
    });
  };

  const showGSCenterCTAModal = () => {
    openModal({
      component: <GrowthServiceCenterCTAModal template_id={id} />,
      className: 'GS--Modal',
    });
  };

  const handleConnectedBankingFlow = () => {
    setBaseLocation('/connected-banking/icici-linked-ca');
    setActivePageName('Connected Banking');
  };

  const showThankYouModal = (trackingPayload, event_type) => {
    return sendDataToSalesForce(trackingPayload, user, event_type).then((resp) => {
      const { success } = resp;
      if (success) {
        openModal({
          size: 'medium',
          component: (
            <ThankYouModal
              handleClose={closeModal}
              imgSrc={`${window.cdnBaseUrl}/static/assets/growth-assets/illustrations/contact_support.svg`}
              config={{
                headerText: 'Request received!',
                bodyText: "We'll be in touch with you about the next steps soon.",
                primaryCtaText: '', // add cta to show button
              }}
            />
          ),
        });
      }
    });
  };
  switch (type) {
    case 'MODAL':
      switch (variant) {
        case 'default':
          return showGSModal;
        case 'center-cta':
          return showGSCenterCTAModal;
        default:
          break;
      }
      break;
    default:
  }

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
    case 'MAR22-SHOPIFY-RZP-NEW-CTA2':
      return () =>
        showThankYouModal(
          { Campaign_ID: 'MAR22-SHOPIFY-RZP-NEW', product_name: 'Payment Gateway' },
          'SHOPIFY_MIGRATION_REQUEST',
        );
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
