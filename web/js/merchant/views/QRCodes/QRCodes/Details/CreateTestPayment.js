import { Component } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm } from 'redux-form';
import AsyncButton from 'react-async-button';
import ModalHeader from 'common/ui/ModalHeader';
import InputField from 'common/ui/Forms/InputField';
import { showNotification } from 'merchant_common/reducers/notifications';
import { closeModal } from 'merchant_common/reducers/modals';
import { createTestPayment } from './model';
import { validateAmount } from 'common/utils/validators';
import { compose } from 'redux';

class CreateTestPayment extends Component {
  state = {};

  componentDidMount() {
    this.props.onMount && this.props.onMount(this.props.qrCode && this.props.qrCode.id);
  }

  componentWillUnmount() {
    this.props.onUnmount && this.props.onUnmount(this.props.qrCode && this.props.qrCode.id);
  }

  createTestPayment = (props) => {
    let { qrCode, mode } = this.props;
    let bankAccount = qrCode.receivers[0];

    if (mode === 'test' && props.amount > 1e7) {
      return this.props.showNotification({
        type: 'error',
        message: 'Amount should not be greater than 1Cr. in Test Mode',
      });
    }

    let fieldProps = {
      ...props,
      payee_account: bankAccount.account_number,
      payee_ifsc: bankAccount.ifsc,
      payer_account: '765432123456789',
      payer_ifsc: 'RAZR0000001',
      transaction_id: Math.floor((+new Date() + (Math.random() * 90 + 10)) / 10),
      time: +new Date(),
    };

    return createTestPayment(fieldProps)
      .then(() => {
        this.props.closeModal();

        this.props.showNotification({
          type: 'success',
          message: 'Test Payment successful',
        });

        this.props.fetchQRCodeDetails();
        this.props.fetchQRPayments();
      })
      .catch(({ errors }) => {
        const error = (errors || [])[0];

        this.props.showNotification({
          type: 'error',
          message: error,
        });
      });
  };

  render() {
    const { handleSubmit } = this.props;

    return (
      <div>
        <ModalHeader title="Create a Test Payment" onCloseClick={this.props.closeModal} />

        <div className="modal-body">
          <form onSubmit={handleSubmit(this.createTestPayment)}>
            <div className="form-group">
              <label>Amount (INR)</label>
              <Field
                name="amount"
                className="form-control"
                component={InputField}
                placeholder="Amount in INR"
                autoFocus={true}
                validate={amountValidator}
              />
            </div>

            <div className="Modal__actions">
              <AsyncButton
                className="btn btn-primary btn-block"
                text="Create"
                pendingText="Creating..."
                onClick={handleSubmit(this.createTestPayment)}
              />
            </div>
          </form>
        </div>
      </div>
    );
  }
}

function amountValidator(value) {
  return validateAmount(value);
}

export default compose(
  connect(
    (state) => {
      return {
        mode: state.session.mode,
      };
    },
    {
      closeModal,
      showNotification,
    },
  ),
  reduxForm({
    form: 'createQRTestPayment',
  }),
)(CreateTestPayment);
