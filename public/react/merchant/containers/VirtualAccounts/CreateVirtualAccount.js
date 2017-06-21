import { Component } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm, formValueSelector } from 'redux-form';
import AsyncButton from 'react-async-button';
import ModalHeader from 'rzp/ui/ModalHeader';
import { showNotification } from 'rzp/modules/notifications';
import { closeModal } from 'rzp/modules/modals';
import { luminateRow } from 'merchant/modules/app';
import { saveVirtualAccount } from 'merchant/modules/virtualaccounts';
import { fetchConfig } from 'merchant/modules/config';
import CustomClipboard from 'rzp/ui/Clipboard/Custom';

const VirtualAccountDetails = ({ virtualAccount }) => {
  return (
    <div>
      <p class="text-muted">
        Share the following information with the customer to accept payments
      </p>

      <div class="form-group">
        <div class="text-muted">Account Number</div>
        <div><b>{virtualAccount.bank_account.account_number}</b></div>
      </div>

      <div class="form-group">
        <div class="text-muted">Beneficiary Name</div>
        <div><b>{virtualAccount.name}</b></div>
      </div>

      <div class="form-group">
        <div class="text-muted">IFSC Code</div>
        <div><b>{virtualAccount.bank_account.ifsc}</b></div>
      </div>

      <CustomClipboard
        value={`Account Number: ${virtualAccount.bank_account.account_number}\nBeneficiary Name: ${virtualAccount.name}\nIFSC: ${virtualAccount.bank_account.ifsc}`}
      >
        <button type="button" class="btn btn-primary btn-block">
          Copy details to Clipboard
        </button>
      </CustomClipboard>
    </div>
  );
};

const selector = formValueSelector('createVirtualAccount');
@connect(
  state => {
    return {
      descriptor: selector(state, 'descriptor'),
      ...state.config,
    };
  },
  {
    luminateRow,
    closeModal,
    showNotification,
    saveVirtualAccount,
    fetchConfig,
  }
)
@reduxForm({
  form: 'createVirtualAccount',
})
export default class CreateVirtualAccount extends Component {
  state = {};

  componentWillMount() {
    this.props.fetchConfig();
  }

  save = props => {
    return this.props
      .saveVirtualAccount(props)
      .then(virtualAccount => {
        this.props.luminateRow(virtualAccount.id);
        this.props.showNotification({
          type: 'success',
          message: 'Virtual Account created',
        });
        this.setState({ virtualAccount });
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
  };

  render() {
    const { handleSubmit, handle = '', descriptor = '' } = this.props;
    const { virtualAccount } = this.state;

    return (
      <div>
        <ModalHeader
          title={
            virtualAccount
              ? 'Virtual Account Created'
              : 'Create Virtual Account'
          }
          onCloseClick={this.props.closeModal}
        />

        <div class="modal-body">
          {virtualAccount
            ? <VirtualAccountDetails virtualAccount={virtualAccount} />
            : <form onSubmit={handleSubmit(this.save)}>
                <div class="form-group">
                  <label>Account Description</label>
                  <Field
                    name="description"
                    class="form-control"
                    component="input"
                    required={true}
                    autoFocus={true}
                  />
                  <small class="help-block">
                    Account description is only displayed on the dashboard and is not shared with the customer.
                  </small>
                </div>

                <div class="form-group">
                  <label>Descriptor</label>
                  <Field
                    name="descriptor"
                    component="input"
                    class="form-control"
                    placeholder="Accepts alphanumberic, upto 10 chars"
                    normalize={value => value.toUpperCase()}
                    onChange={event => {
                      let value = event.target.value;
                      if (/^[a-z0-9]{0,10}$/i.test(value)) {
                        this.props.change('descriptor', value);
                      } else {
                        event.preventDefault();
                      }
                    }}
                  />
                  <small class="help-block">
                    Descriptor will be a part of the account number generated.
                  </small>
                </div>

                <div class="Modal__actions clearfix">
                  {handle
                    ? <div class="pull-left">
                        <div>Account Number</div>
                        <b>
                          RZRP
                          {handle.padStart(4, '×')}
                          {descriptor.padStart(10, '×')}
                        </b>
                      </div>
                    : null}
                  <AsyncButton
                    class="btn btn-primary pull-right"
                    text="Create"
                    pendingText="Creating..."
                    onClick={handleSubmit(this.save)}
                  />
                </div>
              </form>}
        </div>
      </div>
    );
  }
}
