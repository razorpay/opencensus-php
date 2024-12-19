import React from 'react';
import PartnerOnbr from 'merchant/views/PartnerDashboard/Onboarding/partnerOnbr';

const openProductModal = (value, openModal, closeModal, { deviceType }) => {
  // Decide which modal to show based on `value`
  switch (value) {
    case 'partners_onboarding_modal':
      openModal({
        size: 'xlarge',
        disableClose: false,
        component: <PartnerOnbr closeModal={closeModal} disableClose={false} />,
        className:
          deviceType == 'mobile'
            ? 'partner-onboarding-popup mobile-app-popup'
            : 'partner-onboarding-popup',
      });
      break;
    default:
      break;
  }
};

export default openProductModal;
