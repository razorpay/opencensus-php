import { useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

//redux actions
import { activateB2bAccounts } from 'merchant/reducers/b2bExports/actions';
import { closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

//analytics
import { trackActivateClick, trackAccountActivated, trackAccountError } from './analytics';

//components
import ModalHeader from 'common/ui/ModalHeader';
import Input from 'common/new-ui/Input';

const AcknowledgementPopup = ({
  isActivating,
  activateB2bAccounts,
  showNotification,
  closeModal,
}) => {
  const [isChecked, setIsChecked] = useState(true);

  //functions
  const onChange = ({ target }) => {
    setIsChecked(target.checked);
  };

  const onRequest = async () => {
    try {
      trackActivateClick();
      const response = await activateB2bAccounts();
      if (response?.success) {
        trackAccountActivated();
        closeModal();
        showNotification({
          type: 'success',
          message: 'Accounts have been successfully created!',
        });
      }
    } catch (error) {
      trackAccountError(error?.errors);
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
        <button
          className="pull-right btn btn-primary"
          disabled={isActivating || !isChecked}
          onClick={onRequest}
          type="button"
        >
          {isActivating ? 'Processing...' : 'Activate Now'}
        </button>
      </div>
    </div>
  );
};

const mapStateToProps = (state) => ({
  isActivating: state.b2bExportsAccounts.isActivating,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators({ activateB2bAccounts, showNotification, closeModal }, dispatch);

export default connect(mapStateToProps, mapDispatchToProps)(AcknowledgementPopup);
