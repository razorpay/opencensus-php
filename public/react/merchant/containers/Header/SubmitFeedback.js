import { Component, PropTypes } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm } from 'redux-form';
import AsyncButton from 'react-async-button';
import InputField from 'rzp/ui/Forms/InputField';
import ModalHeader from 'rzp/ui/ModalHeader';
import { required } from 'rzp/utils/validators';
import { closeModal } from 'rzp/modules/modals';
import { showNotification } from 'rzp/modules/notifications';
import { submitFeedback } from 'merchant/modules/session';

@connect(state => state.session, {
  closeModal,
  showNotification,
  submitFeedback,
})
@reduxForm({
  form: 'submitFeedback',
})
export default class SubmitFeedback extends Component {
  _submit = props => {
    return this.props
      .submitFeedback({
        ...props,
        message: props.message || '',
        email: this.props.user.user.email,
      })
      .then(() => {
        this.props.showNotification({
          type: 'success',
          message: 'Submitted! Thanks for your feedback.',
        });
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
  };

  submit = props => {
    return this._submit({
      ...props,
      subject: 'New Dashboard Feedback',
    }).then(() => {
      this.props.closeModal();
    });
  };

  render() {
    const { handleSubmit } = this.props;
    let title = 'Feedback / Suggestion';
    let label = 'Your message';

    return (
      <div>
        <ModalHeader title={title} onCloseClick={this.props.closeModal} />

        <div class="modal-body">
          <form onSubmit={handleSubmit(this.submit)}>
            <div class="form-group">
              <label class="label-required">
                {label}
              </label>
              <div>
                <Field
                  name="message"
                  component={InputField}
                  tagName="textarea"
                  class="form-control"
                  autoFocus={true}
                  rows={10}
                  placeholder="We would love to know your thoughts"
                  validate={required()}
                />
              </div>
            </div>

            <div class="Modal__actions">
              <AsyncButton
                type="submit"
                class="btn btn-primary btn-lg btn-block"
                text="Submit Feedback"
                onClick={handleSubmit(this.submit)}
              />
            </div>
          </form>
        </div>
      </div>
    );
  }
}
