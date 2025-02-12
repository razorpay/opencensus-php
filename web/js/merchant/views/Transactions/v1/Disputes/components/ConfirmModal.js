import React from 'react';
import PropTypes from 'prop-types';
import AsyncButton from 'react-async-button';
import { connect } from 'react-redux';

import { useSplitzService } from 'common/splitz';
import ModalHeader from 'common/ui/ModalHeader';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { fetchDisputes } from 'merchant/reducers/collection';
import { accept, fetchOpen } from 'merchant/reducers/disputes/details';
import { fetchIsAdminAsMerchant } from 'merchant/reducers/profile';
import { isTransactionsV2Enabled } from 'merchant/views/Transactions/v2/common/utils';

const ConfirmModal = (props) => {
  const {
    context,
    dispute,
    closeModal,
    dispatch,
    showNotification,
    title,
    description,
    onConfirm,
    user,
    isAdminAsMerchant,
  } = props;
  const splitz = useSplitzService();
  const version = isTransactionsV2Enabled(splitz, user) ? 'v2' : undefined;

  React.useEffect(() => {
    const { loading, error } = isAdminAsMerchant;
    if (loading && error === null) dispatch(fetchIsAdminAsMerchant());
  }, []);

  const handlePrimaryClick = () => {
    if (context === 'accept') {
      analyticsTrack({
        objectName: 'dispute presentment',
        actionName: 'accept dispute',
        screen: 'disputes',
        properties: {
          timestamp: Date.now(),
          version,
          disputeId: dispute.id,
          isAdminAsMerchant: isAdminAsMerchant?.data,
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
    if (context === 'submit' && onConfirm) {
      onConfirm();
    }
    return null;
  };
  return (
    <div className="dispute-confirmation">
      <ModalHeader title={title} />
      <div className="modal-body">
        <div className="alert alert-warning">{description}</div>
        <div className="dispute-cta">
          <button className="btn btn-outline" type="button" onClick={closeModal}>
            No, Don&#39;t!
          </button>
          <AsyncButton
            text={`Yes, ${context === 'accept' ? 'Accept' : 'Contest'}`}
            pendingText="Please wait..."
            className="btn btn-primary"
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

export default connect(
  (state) => ({ user: state.session.user, isAdminAsMerchant: state.profile.isAdminAsMerchant }),
  (dispatch) => ({ dispatch }),
)(ConfirmModal);
