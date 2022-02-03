import React from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { ACTION_REQUIRED, REJECTED } from '../../constants';
import Clarifications from '../Modals/Clarifications';

const RejectedAndActionRequired = (props) => {
  const {
    instrument,
    user: { isSmartDashboardActive },
  } = props;
  const { status, merchant_instrument_request_id, comment } = instrument;

  const handleUpdateForm = (mirId) => {
    return props.openModal({
      component: (
        <Clarifications
          onCloseClick={props.closeModal}
          mirId={mirId}
          status={status}
          instrument={instrument}
        />
      ),
      className: 'clarifications-modal',
    });
  };

  return (
    <div className="comment" title={comment}>
      <img
        src="https://cdn.razorpay.com/static/assets/instrument-request/alert-triangle.svg"
        alt="alert"
        height="15px"
        width="15px"
      />
      <p>
        {isSmartDashboardActive && [ACTION_REQUIRED, REJECTED].includes(status) ? (
          <>
            <span>We need more information to proceed further with the application,</span>{' '}
            <a onClick={() => handleUpdateForm(merchant_instrument_request_id)}>
              update Request Form.
            </a>
          </>
        ) : !isSmartDashboardActive ? (
          comment
        ) : (
          <span>No comments available</span>
        )}
      </p>
    </div>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      openModal,
      closeModal,
    },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(RejectedAndActionRequired);
