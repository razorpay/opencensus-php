import { Component } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm } from 'redux-form';
import AsyncButton from 'react-async-button';
import ModalHeader from 'rzp/ui/ModalHeader';
import InputField from 'rzp/ui/Forms/InputField';
import { required } from 'rzp/utils/validators';
import * as ModalActions from 'rzp/modules/modals';
import * as NotificationsActions from 'rzp/modules/notifications';

@connect(null, {
  ...ModalActions,
  ...NotificationsActions,
})
@reduxForm({
  form: 'addInternalNote',
})
export default class AddInternalNote extends Component {
  addInternalNote = props => {
    let notes = {};
    notes[props.key] = props.value;

    return this.props
      .onSave(notes)
      .then(invoice => {
        this.props.showNotification({
          type: 'success',
          message: 'Internal Note added',
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
    const { handleSubmit, invalid } = this.props;

    return (
      <div>
        <ModalHeader
          title="Add Internal Note"
          onCloseClick={this.props.closeModal}
        />

        <form class="form-horizontal">
          <div class="modal-body">
            <p class="help-block">
              Internal notes will only be visible on this dashboard or
              accessible through the API. It won't be visible to the customer.
            </p>

            <div class="form-group">
              <div class="col-md-12">
                <label>
                  <b>Title</b>
                </label>
                <Field
                  name="key"
                  component={InputField}
                  class="form-control"
                  autoFocus={true}
                  validate={required()}
                />
              </div>
            </div>

            <div class="form-group">
              <div class="col-md-12">
                <label>
                  <b>Description</b>
                </label>
                <Field
                  name="value"
                  component={InputField}
                  tagName="textarea"
                  class="form-control"
                  validate={required()}
                />
              </div>
            </div>

            <div class="Modal__actions">
              <AsyncButton
                type="submit"
                class="btn btn-primary btn-block btn-lg"
                text="Add Internal Note"
                disabled={invalid}
                onClick={handleSubmit(this.addInternalNote)}
              />
            </div>
          </div>
        </form>
      </div>
    );
  }
}
