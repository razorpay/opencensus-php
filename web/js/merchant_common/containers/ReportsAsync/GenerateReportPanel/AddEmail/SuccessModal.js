import React, { useState, useEffect } from 'react';
import { withRouter } from 'react-router-dom';
import { connect } from 'react-redux';
import { compose } from 'redux';
import { closeModal as fnCloseModal } from 'merchant_common/reducers/modals';
import { fetchUser as fetchUserReducer } from 'merchant/reducers/session';
import { AsyncBtn } from 'common/new-ui/Button';

const SuccessModal = ({
  heading,
  note,
  successButtonText,
  successButtonLink,
  history,
  onSubmit,
  closeModal,
  fetchUser,
}) => {
  const [isRefreshing, setIsRefreshing] = useState(false);
  const onSuccess = () => {
    if (onSubmit) {
      onSubmit();
    }
    closeModal();
    if (successButtonLink) {
      history.push(successButtonLink);
    }
  };

  useEffect(() => {
    setIsRefreshing(true);
    fetchUser().then(() => {
      setIsRefreshing(false);
    });
  }, []);

  return (
    <div className="add-email-modal-content">
      <div className="merchant-heading">{heading}</div>
      <div className="merchant-note email-merchant-note">{note}</div>

      <AsyncBtn.Primary className="Button--full-width" onClick={onSuccess} disabled={isRefreshing}>
        {isRefreshing ? 'Updating...' : successButtonText ? successButtonText : 'Go to Dashboard'}
      </AsyncBtn.Primary>
    </div>
  );
};

export default withRouter(
  compose(connect(null, { closeModal: fnCloseModal, fetchUser: fetchUserReducer }))(SuccessModal),
);
