import React from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { Alert } from '@razorpay/blade/components';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

const ComingSoon = () => {
  return (
    <div className="coming-soon-alert">
      <Alert
        emphasis="subtle"
        title="Coming Soon - Widgets with Conversion Analytics & more"
        isDismissible={false}
        isFullWidth
        color="information"
      />
    </div>
  );
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      openModal,
      closeModal,
      showNotification,
    },
    dispatch,
  );

export default connect(null, mapDispatchToProps)(ComingSoon);
