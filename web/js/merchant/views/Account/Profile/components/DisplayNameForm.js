import React, { PureComponent } from 'react';
import { connect } from 'react-redux';
import AsyncButton from 'react-async-button';
import ModalHeader from 'common/ui/ModalHeader';
import { reduxForm, Field } from 'redux-form';
import { required } from 'common/utils/validators';
import InputField from 'common/ui/Forms/InputField';

import { closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

@connect(null, { closeModal, showNotification })
@reduxForm({
  form: 'updateDisplayNameForm',
})
export default class DisplayNameForm extends PureComponent {
  constructor(props) {
    super(props);

    this.props.initialize({
      display_name: props.displayName,
    });
  }

  setDisplayName = this.setDisplayName.bind(this);

  setDisplayName(props) {
    this.props.change('display_name', this.props.displayName);
  }

  render() {
    const { handleSubmit } = this.props;
    return (
      <form onSubmit={handleSubmit(this.props.updateDisplayName)}>
        <ModalHeader
          title="Edit Display Name"
          onCloseClick={this.props.closeModal}
        />
        <div class="modal-body">
          <div class="form-group">
            <label class="label-required">Display Name</label>
            <div class="pull-right">
              <button
                type="button"
                class="btn btn-link no-padding"
                onClick={this.setDisplayName}
              >
                Reset
              </button>
            </div>
            <Field
              label="Display Name"
              component={InputField}
              placeholder="Display Name"
              name="display_name"
              class="form-control"
              validate={required()}
              autoFocus={true}
            />
            <small class="help-block">
              This is the display name that you and your team will see on the
              Razorpay dashboard.
            </small>
          </div>

          <div class="Modal__actions">
            <AsyncButton
              type="submit"
              class="btn btn-primary btn-block"
              text="Update"
              pendingText="Updating..."
              onClick={handleSubmit(this.props.updateDisplayName)}
            />
          </div>
        </div>
      </form>
    );
  }
}
