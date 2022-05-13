import React, { useEffect } from 'react';
import { ModalBody, ModalHeader, CloseIconContainer } from 'common/components/Modal/Styled';
import Button from '@razorpay/blade-old/src/atoms/Button';
import { sendDataToSalesForce } from '../../utils/common-api';
import { closeModal as closeModalProp } from 'merchant_common/reducers/modals';
import { compose } from 'redux';
import { connect } from 'react-redux';
import rTracking from 'react-tracking';
import Loader from 'common/ui/Loader';
import { withRouter } from 'react-router-dom';
import { fetchGSModal as fetchGSModalProp } from 'merchant/reducers/growthService';

const ThankYouModal = ({ loading, gs_modals, user, closeModal, fetchGSModal, template_id }) => {
  useEffect(() => {
    fetchGSModal({ template_id });
  }, []);

  if (!loading) {
    if (Object.keys(gs_modals).length === 0) {
      closeModal();
    } else {
      sendDataToSalesForce(
        {
          id: gs_modals?.id,
          product_name: gs_modals?.product_name,
          event_type: gs_modals?.event_type,
        },
        user,
      );
      return (
        <div className="thank-you-gs-modal">
          <CloseIconContainer data-testid="modalCloseButton" onClick={closeModal}>
            <Button
              variant="tertiary"
              size="small"
              variantColor="shade"
              icon="close"
              type="button"
            />
          </CloseIconContainer>
          <ModalHeader>&nbsp;</ModalHeader>
          <div className="img-container">
            <img src={gs_modals?.image?.url} alt={gs_modals?.image?.alt_text} />
          </div>
          <ModalHeader>{gs_modals?.footer_data?.header}</ModalHeader>
          <ModalBody className="tm-footer">{gs_modals?.footer_data?.label}</ModalBody>
        </div>
      );
    }
  }

  return (
    <>
      <button type="button" id="gsBtnClose" onClick={closeModal}>
        <i className="i i-close" />
      </button>
      <div id="gsModalLoader">
        <Loader />
      </div>
    </>
  );
};

export default compose(
  rTracking(() => window.rzpQ.component('growthServiceModal')),
  withRouter,
  connect(
    (state) => {
      return {
        ...state.session.user,
        ...state.growthService.gs_modals,
      };
    },
    {
      fetchGSModal: fetchGSModalProp,
      closeModal: closeModalProp,
    },
  ),
)(ThankYouModal);
