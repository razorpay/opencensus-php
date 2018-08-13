import React, { PureComponent } from 'react';
import { connect } from 'react-redux';
import AsyncButton from 'react-async-button';
import ModalHeader from 'rzp/ui/ModalHeader';
import { reduxForm, Field } from 'redux-form';
import { required } from 'rzp/utils/validators';
import InputField from 'rzp/ui/Forms/InputField';

import { closeModal } from 'rzp/modules/modals';
import { showNotification } from 'rzp/modules/notifications';

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

  render() {
    const { handleSubmit } = this.props;
    return (
      <form onSubmit={handleSubmit(this.props.updateDisplayName)}>
        <ModalHeader
          title="Update Display name"
          onCloseClick={this.props.closeModal}
        />
        <div class="modal-body">
          <div class="form-group">
            <Field
              component={InputField}
              placeholder="Display Name"
              name="display_name"
              class="form-control"
              validate={required()}
              autoFocus={true}
            />
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
