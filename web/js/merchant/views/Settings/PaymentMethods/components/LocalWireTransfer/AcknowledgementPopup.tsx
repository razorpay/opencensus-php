import React, { useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

//redux actions
import { closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { activateAccount } from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/services';

//analytics
import {
  trackActivateClick,
  trackAccountActivated,
  trackAccountError,
} from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/analytics';

//utils
import { PopupPropsInterface } from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/types';
import { VA_USD } from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/constants';

//components
import ModalHeader from 'common/ui/ModalHeader';
import Input from 'common/new-ui/Input';
import { Button } from '@razorpay/blade/components';

const AcknowledgementPopup: React.FC<PopupPropsInterface> = ({
  showNotification,
  closeModal,
  activateAccount,
}) => {
  const [isChecked, setIsChecked] = useState<boolean>(true);
  const [isLoading, setIsLoading] = useState(false);

  //functions
  const onChange = ({ target }) => {
    setIsChecked(target.checked);
  };

  /**
   * Api call to request for ACH bank account
   */
  const onRequest = async () => {
    try {
      trackActivateClick(VA_USD);
      setIsLoading(true);
      const response: any = await activateAccount(VA_USD, 1); // eslint-disable-line
      setIsLoading(false);
      if (response?.success) {
        trackAccountActivated(VA_USD);
        closeModal();
        showNotification({
          type: 'success',
          message: 'Account have been successfully created!',
        });
      }
    } catch ({ errors }) {
      const error = Array.isArray(errors) ? errors[0] : errors;
      trackAccountError(error, VA_USD);
      setIsLoading(false);
      showNotification({
        type: 'error',
        message: error,
      });
    }
  };

  return (
    <div className="b2b-acknowledgement-popup">
      <ModalHeader title="Request for USD currency bank account" onCloseClick={closeModal} />
      <div className="modal-body">
        <p>
          You will be able to accept USD payments via ACH bank transfer with your local currency
          bank account
        </p>
        <div className="checkbox-wrapper">
          <Input.Check checked={isChecked} onChange={onChange} autoRender />
          <span>
            I agree to the{' '}
            <a
              className="text-primary"
              target="_blank"
              rel="noopener noreferrer"
              href="https://razorpay.com/terms/local-bank-transfer"
            >
              Terms and Conditions
              <i className="i i-external-link" />
            </a>
          </span>
        </div>
        <Button
          variant="primary"
          isLoading={isLoading}
          isDisabled={!isChecked}
          isFullWidth
          onClick={onRequest}
        >
          Activate Now
        </Button>
      </div>
    </div>
  );
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators({ showNotification, closeModal, activateAccount }, dispatch);

export default connect(null, mapDispatchToProps)(AcknowledgementPopup);
