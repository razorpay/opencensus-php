import React from 'react';
import PropTypes from 'prop-types';
import ModalHeader from 'common/ui/ModalHeader';
import AsyncButton from 'react-async-button';
import Amount from 'common/ui/Amount';
import { connect } from 'react-redux';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { fetchDisputes } from 'merchant/reducers/collection';
import { accept, fetchOpen } from 'merchant/reducers/disputes/details';

const ConfirmModal = (props) => {
  const { context, dispute, closeModal, dispatch, showNotification } = props;
  const title =
    context === 'accept'
      ? 'Are you sure you want to accept this chargeback?'
      : 'Are you sure you want to cancel evidence submission?';

  const handlePrimaryClick = () => {
    if (context === 'accept') {
      analyticsTrack({
        objectName: 'dispute presentment',
        actionName: 'accept dispute',
        screen: 'disputes',
        properties: {
          timestamp: Date.now(),
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      });
      return dispatch(accept(dispute.id))
        .then(() => {
          dispatch(fetchDisputes({ skip: 0, count: 25 }));
          dispatch(fetchOpen());
          closeModal();
        })
        .catch((err) => {
          closeModal();
          showNotification({
            type: 'error',
            message: err.errors,
          });
        });
    }
    return null;
  };
  return (
    <div class="dispute-confirmation">
      <ModalHeader title={title} />
      <div class="modal-body">
        <div class="alert alert-warning">
          {context === 'accept' ? (
            <>
              <Amount value={dispute.amount} currency={dispute.currency} />
              &nbsp; will be immediately deducted from your Razorpay account balance
            </>
          ) : (
            <>The files and details you’ve uploaded as evidence will not be submitted</>
          )}
        </div>
        <div class="dispute-cta">
          <button class="btn btn-outline" type="button" onClick={closeModal}>
            No, Don&#39;t!
          </button>
          <AsyncButton
            text={`Yes, ${context === 'accept' ? 'Accept' : 'Cancel'}`}
            pendingText="Please wait..."
            class="btn btn-primary"
            onClick={handlePrimaryClick}
          />
        </div>
      </div>
    </div>
  );
};

ConfirmModal.propTypes = {
  context: PropTypes.string.isRequired,
  dispute: PropTypes.object.isRequired,
  closeModal: PropTypes.func.isRequired,
  dispatch: PropTypes.func.isRequired,
  showNotification: PropTypes.func.isRequired,
};

export default connect(null, (dispatch) => ({
  dispatch,
}))(ConfirmModal);
