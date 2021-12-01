import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';
import {
  openModal as fnOpenModal,
  closeModal as fnCloseModal,
} from 'merchant_common/reducers/modals';
import AddEmailModal from './index';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

const RequestEmailModal = ({ openModal, closeModal }) => {
  const openAddEmailModal = () => {
    analyticsTrack({
      objectName: 'add email prompt update',
      actionName: 'clicked',
      screen: 'home page',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });

    openModal({
      size: 'small',
      component: <AddEmailModal screen="home page" />,
    });
  };

  useEffect(() => {
    analyticsTrack({
      objectName: 'add email prompt',
      actionName: 'displayed',
      screen: 'home page',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  }, []);

  const onPopupClose = () => {
    analyticsTrack({
      objectName: 'add email prompt cancel',
      actionName: 'clicked',
      screen: 'home page',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    closeModal();
  };

  return (
    <div className="add-email-modal-content request-email-modal-content">
      <button type="button" className="close" onClick={onPopupClose}>
        <i className="i i-close" />
      </button>
      <img
        className="modal-hero-image"
        src="https://cdn.razorpay.com/static/assets/modal-asset/request-email.svg"
        alt=""
      />
      <div className="merchant-heading">Receive important updates on email</div>
      <div className="merchant-note">
        {
          'Update your email address on your Razorpay account to make sure you don’t miss important alerts.'
        }
      </div>
      <div className="merchant-actions">
        <button type="button" className="btn btn-link" onClick={onPopupClose}>
          Later
        </button>
        <button type="button" className="btn btn-primary" onClick={openAddEmailModal}>
          Update Now
        </button>
      </div>
    </div>
  );
};

export default compose(connect(null, { openModal: fnOpenModal, closeModal: fnCloseModal }))(
  RequestEmailModal,
);
