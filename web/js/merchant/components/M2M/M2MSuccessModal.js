import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { ModalMask, Modal } from 'common/new-ui/Modal';
import { getFormattedAmountNew } from 'common/utils/rzp-utils';
import { updateModalConfigDetails } from 'merchant/reducers/ModalConfigApi';
import * as EventActions from 'merchant/reducers/trackEvents';

const M2MSuccessModal = ({ referredMerchants, referredAmount, trackEvents, isReferee }) => {
  const [showModal, setShowModal] = useState(true);
  const onModalClose = () => {
    if (isReferee) {
      updateModalConfigDetails({ referee_success_popup_count: 0 }, 'onboarding');
    } else {
      updateModalConfigDetails({ referral_success_popup_count: 0 }, 'onboarding');
    }
    setShowModal(false);
  };

  useEffect(() => {
    trackEvents({
      objectName: 'Referral Success Modal',
      actionName: 'Viewed',
      screen: 'home page',
      toCleverTap: true,
      properties: {
        referralName: referredMerchants,
      },
    });

    window.trackhubs?.({
      name: 'update_property',
      data: {
        referral_success_modal: true,
      },
    });
  }, []);

  let title = '';
  let description = '';
  const formattedReferredAmount = getFormattedAmountNew(referredAmount, true);
  if (isReferee) {
    title = `Congratulations! Your first ${formattedReferredAmount} in collections are on us - 100% FREE*`;
    description = `No charges, no fees on your next ${formattedReferredAmount} in collections. Keep growing your business.`;
  } else if (referredMerchants.length > 1) {
    title = `Congratulations! ${referredMerchants.length} of your friends have started using Razorpay to grow their business`;
    description = `No charges, no fees on your next ${formattedReferredAmount} in collections. Keep growing your business. Continue helping others in your network!`;
  } else if (referredMerchants.length === 1) {
    title = `Congratulations! Your friend ${referredMerchants[0]} has started using Razorpay to grow their business`;
    description = `No charges, no fees on your next ${formattedReferredAmount} in collections. Keep growing your business. Continue helping others in your network!`;
  }
  return showModal && title ? (
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
