import { Component } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm } from 'redux-form';
import AsyncButton from 'react-async-button';
import ModalHeader from 'common/ui/ModalHeader';
import InputField from 'common/ui/Forms/InputField';
import { amount } from 'common/utils/validators';
import { showNotification } from 'merchant_common/reducers/notifications';
import { closeModal } from 'merchant_common/reducers/modals';
import { createTestPayment } from 'merchant/reducers/virtualaccounts';
import { fetchItem, fetchVAPayments } from 'merchant/reducers/virtualaccounts';
import { validateAmount } from 'common/utils/validators';

@connect(
  (state) => {
    return {
      mode: state.session.mode,
    };
  },
  {
    closeModal,
    showNotification,
    fetchItem,
    fetchVAPayments,
    createTestPayment,
  },
)
export default class CreateTestPayment extends Component {
  state = {};

  componentDidMount() {
    this.props.onMount &&
      this.props.onMount(this.props.virtualAccount && this.props.virtualAccount.id);
  }

  componentWillUnmount() {
    this.props.onUnmount &&
      this.props.onUnmount(this.props.virtualAccount && this.props.virtualAccount.id);
  }

  createTestPayment = (props) => {
    let { virtualAccount, mode } = this.props;
    let bankAccount = virtualAccount.receivers[0];

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
    return this.props
      .createTestPayment(fieldProps)
      .then(() => {
        this.props.onTestPayment && this.props.onTestPayment(props);
        this.props.closeModal();
        this.props.showNotification({
          type: 'success',
          message: 'Test Payment successful',
        });
        this.props.fetchItem(virtualAccount.id);
        this.props.fetchVAPayments(virtualAccount.id);
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
  };

  render() {
    const { handleSubmit } = this.props;

    return (
      <div>
        <ModalHeader title="Create a Test Payment" onCloseClick={this.props.closeModal} />

        <div class="modal-body">
          <form onSubmit={handleSubmit(this.createTestPayment)}>
            <div class="form-group">
              <label>Amount (INR)</label>
              <Field
                name="amount"
                class="form-control"
                component={InputField}
                placeholder="Amount in INR"
                autoFocus={true}
                validate={amountValidator}
              />
            </div>

            <div class="Modal__actions">
              <AsyncButton
                class="btn btn-primary btn-block"
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
