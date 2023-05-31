import React, { useEffect } from 'react';
import { ModalBody, ModalHeader, CloseIconContainer } from 'common/components/Modal/Styled';
import Button from '@razorpay/blade-old/src/atoms/Button';
import { closeModal as closeModalProp } from 'merchant_common/reducers/modals';
import { compose, bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import rTracking from 'react-tracking';
import Loader from 'common/ui/Loader';
import { withRouter } from 'react-router-dom';
import { fetchGSModal as fetchGSModalProp } from 'merchant/reducers/growthService';
import './GSModalStyle.styl';

const ThankYouModal = ({ loading, gs_modals, closeModal, fetchGSModal, template_id }) => {
  useEffect(() => {
    fetchGSModal({ template_id });
  }, []);

  if (!loading) {
    if (Object.keys(gs_modals).length === 0) {
      closeModal();
    } else {
      return (
        <div className="thank-you-gs-modal">
          <CloseIconContainer data-testid="modal-close-button" onClick={closeModal}>
            <Button
              variant="tertiary"
              size="small"
              variantColor="shade"
              icon="close"
              type="button"
            />
          </CloseIconContainer>
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
      <button type="button" id="gs-btn-close" onClick={closeModal}>
        <i className="i i-close" />
      </button>
      <div id="gs-modal-loader">
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
        ...state?.growthService?.gs_modals,
      };
    },
    (dispatch) => {
      return bindActionCreators(
        {
          fetchGSModal: fetchGSModalProp,
          closeModal: closeModalProp,
        },
        dispatch,
      );
    },
  ),
)(ThankYouModal);
