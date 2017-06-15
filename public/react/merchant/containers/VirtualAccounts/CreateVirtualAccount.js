import { Component } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm } from 'redux-form';
import AsyncButton from 'react-async-button';
import ModalHeader from 'rzp/ui/ModalHeader';
import { showNotification } from 'rzp/modules/notifications';
import { closeModal } from 'rzp/modules/modals';
import { luminateRow } from 'merchant/modules/app';
import { saveVirtualAccount } from 'merchant/modules/virtualaccounts';

@connect(null, {
  luminateRow,
  closeModal,
  showNotification,
  saveVirtualAccount,
})
@reduxForm({
  form: 'createVirtualAccount',
})
export default class CreateVirtualAccount extends Component {
  save = props => {
    return this.props
      .saveVirtualAccount(props)
      .then(virtualAccount => {
        this.props.luminateRow(virtualAccount.id);
        this.props.showNotification({
          type: 'success',
          message: 'Virtual Account created',
        });
        this.props.closeModal();
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
  };

  render() {
    const { handleSubmit, virtualAccount } = this.props;

    return (
      <div>
        <ModalHeader
          title={'Create Virtual Account'}
          onCloseClick={this.props.closeModal}
        />

        <div class="modal-body">
          <form onSubmit={handleSubmit(this.save)}>
            <div class="form-group">
              <label>Beneficiary Name</label>
              <Field
                name="name"
                class="form-control"
                component="input"
                required={true}
                autoFocus={true}
              />
            </div>

            <div class="form-group">
              <label>Descriptor</label>
              <Field
                name="descriptor"
                component="input"
                class="form-control"
                placeholder="Optional"
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
                Accepts alphanumberic, upto 10 chars
              </small>
            </div>

            <div class="Modal__actions">
              <AsyncButton
                class="btn btn-primary btn-block"
                text="Create"
                pendingText="Creating..."
                onClick={handleSubmit(this.save)}
              />
            </div>
          </form>
        </div>
      </div>
    );
  }
}
