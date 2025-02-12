import { Component } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm } from 'redux-form';
import AsyncButton from 'react-async-button';
import ModalHeader from 'common/ui/ModalHeader';
import InputField from 'common/ui/Forms/InputField';
import { required } from 'common/utils/validators';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import { compose } from 'redux';

class AddInternalNote extends Component {
  addInternalNote = (props) => {
    let notes = {};
    notes[props.key] = props.value;

    return this.props.onSave(notes).then((invoice) => {
      this.props.showNotification({
        type: 'success',
        message: 'Internal Note added',
      });
      this.props.closeModal();
    });
  };

  render() {
    const { handleSubmit, invalid } = this.props;

    return (
      <div>
        <ModalHeader title="Add Internal Note" onCloseClick={this.props.closeModal} />

        <form className="form-horizontal">
          <div className="modal-body">
            <p className="help-block">
              Internal notes will only be visible on this dashboard or accessible through the API.
              It won't be visible to the customer.
            </p>

            <div className="form-group">
              <div className="col-md-12">
                <label>
                  <b>Title</b>
                </label>
                <Field
                  name="key"
                  component={InputField}
                  className="form-control"
                  autoFocus={true}
                  validate={required()}
                />
              </div>
            </div>

            <div className="form-group">
              <div className="col-md-12">
                <label>
                  <b>Description</b>
                </label>
                <Field
                  name="value"
                  component={InputField}
                  tagName="textarea"
                  className="form-control"
                  validate={required()}
                />
              </div>
            </div>

            <div className="Modal__actions">
              <AsyncButton
                type="submit"
                className="btn btn-primary btn-block btn-lg"
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

export default compose(
  connect(null, {
    ...ModalActions,
    ...NotificationsActions,
  }),
  reduxForm({
    form: 'addInternalNote',
  }),
)(AddInternalNote);
