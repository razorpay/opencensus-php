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

const VirtualAccountDetails = ({ virtualAccount }) => {
  return (
    <div>
      <div class="help-block">
        Share the following information with the customer to accept payments
      </div>

      <dl>
        <dt>Account Number</dt>
        <dd>{virtualAccount.bank_details}</dd>

        <dt>Beneficiary Name</dt>
        <dd>{virtualAccount.name}</dd>

        <dt>IFSC Code</dt>
        <dd>{virtualAccount.bank_details}</dd>
      </dl>
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
            ? <VirtualAccountDetails virtualAccount={VirtualAccountDetails} />
            : <form onSubmit={handleSubmit(this.save)}>
                <div class="form-group">
                  <label>Beneficiary Name (Optional)</label>
                  <Field
                    name="name"
                    class="form-control"
                    component="input"
                    required={true}
                    autoFocus={true}
                  />
                  <small class="help-block">
                    Your billing label, Concord Co. will be used as the default beneficiary name
                  </small>
                </div>

                <div class="form-group">
                  <label>Descriptor</label>
                  <Field
                    name="descriptor"
                    component="input"
                    class="form-control"
                    placeholder="Accepts alphanumberic, upto 10 chars"
                    onChange={event => {
                      let value = event.target.value;
                      if (/^[a-z0-9]{5,10}$/i.test(value)) {
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

                <div class="Modal__actions">
                  <div class="">
                    <label>Account Number</label>
                    <b>
                      RZRP
                      {handle.padStart(4, 'X')}
                      {descriptor.padStart(10, 'X')}
                    </b>
                  </div>
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
