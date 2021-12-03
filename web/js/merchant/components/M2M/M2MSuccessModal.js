import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { ModalMask, Modal } from 'common/new-ui/Modal';
import { updateModalConfigDetails } from 'merchant/reducers/ModalConfigApi';
import { getFormattedAmountNew } from 'common/utils/rzp-utils';
import * as EventActions from 'merchant/reducers/trackEvents';

const M2MSuccessModal = ({ referredMerchants, referredMerchantsAmount, trackEvents }) => {
  const [showModal, setShowModal] = useState(true);
  const onModalClose = () => {
    updateModalConfigDetails({ referral_success_popup_count: 0 }, 'onboarding');
    setShowModal(false);
  };

  useEffect(() => {
    trackEvents({
      objectName: 'Referral Success Modal',
      actionName: 'Viewed',
      screen: 'home page',
      referralName: referredMerchants,
    });
  }, []);

  let title, description;
  const referredAmount = getFormattedAmountNew(referredMerchantsAmount, true);
  if (referredMerchants.length > 1) {
    const referredMerchantsNames = referredMerchants.reduce(
      (merchantNames, merchantName, index) => {
        if (index < referredMerchants.length - 1) {
          return `${merchantNames}, ${merchantName}`;
        } else {
          return `${merchantNames} and ${merchantName}`;
        }
      },
    );
    title = `Yay! You referred ${referredMerchants.length} merchants successfully and unlocked ${referredAmount} transaction credits.`;
    description = `${referredMerchantsNames} have started using Razorpay. Your payments processed via Razorpay, up to ${referredAmount}, will be free of charge!`;
  } else {
    title = `Yayy! Double winnings! ${referredMerchants[0]} and you unlocked ${referredAmount} transaction credits.`;
    description = `This means, your payments processed via Razorpay, up to ${referredAmount}, will be free of charge!`;
  }
  return showModal ? (
    <ModalMask>
      <Modal className="m2m-success-modal" onClose={onModalClose}>
        <div className="m2m-success-banner">
          <img src="https://cdn.razorpay.com/m2m/m2m_success_banner.svg" />
        </div>
        <div className="m2m-modal-body">
          <div className="m2m-header">{title}</div>
          <div className="m2m-desc">{description}</div>
          <button className="m2m-btn-close" onClick={() => onModalClose()}>
            Close
          </button>
        </div>
      </Modal>
    </ModalMask>
  ) : null;
};

export default connect(null, { ...EventActions })(M2MSuccessModal);
