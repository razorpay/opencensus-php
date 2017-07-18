import { Component, PropTypes } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm } from 'redux-form';
import AsyncButton from 'react-async-button';
import InputField from 'rzp/ui/Forms/InputField';
import ModalHeader from 'rzp/ui/ModalHeader';
import LocalStorageService from 'rzp/utils/localStorage';
import { required } from 'rzp/utils/validators';
import { closeModal } from 'rzp/modules/modals';
import { showNotification } from 'rzp/modules/notifications';
import { enableOrDisableNewui, submitFeedback } from 'merchant/modules/session';

@connect(state => state.session, {
  closeModal,
  showNotification,
  enableOrDisableNewui,
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
      subject: this.props.user.isNewUIEnabled
        ? 'New Dashboard Feedback'
        : 'Dashboard Feedback',
    }).then(() => {
      this.props.closeModal();
    });
  };

  revert = () => {
    return this.props.enableOrDisableNewui(false);
  };

  submitAndRevert = props => {
    return this._submit({
      ...props,
      subject: 'New Dashboard Revert Feedback',
    }).then(() => {
      return this.revert();
    });
  };

  render() {
    const { handleSubmit, revertToOldDesign } = this.props;
    let title = 'Feedback / Suggestion';
    let label = 'Your message';
    if (revertToOldDesign) {
      title = 'Before you revert...';
      label = 'Why do you want to go back?';
    }

    return (
      <div>
        <ModalHeader title={title} onCloseClick={this.props.closeModal} />

        <div class="modal-body">
          <form onSubmit={handleSubmit(this.submit)}>
            <div class="form-group">
              <label class={revertToOldDesign ? '' : 'label-required'}>
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
                  placeholder={
                    revertToOldDesign
                      ? 'his will help us learn and fix issues'
                      : 'We would love to know your thoughts'
                  }
                  validate={required()}
                />
              </div>
            </div>

            <div class="Modal__actions">
              <AsyncButton
                type="submit"
                class="btn btn-primary btn-lg btn-block"
                text={
                  revertToOldDesign
                    ? 'Submit and revert to old design'
                    : 'Submit Feedback'
                }
                onClick={handleSubmit(
                  revertToOldDesign ? this.submitAndRevert : this.submit
                )}
              />
              {revertToOldDesign &&
                <AsyncButton
                  type="button"
                  class="btn btn-default btn-block"
                  text="Revert without giving feedback"
                  pendingText="Reverting..."
                  onClick={handleSubmit(this.revert)}
                  style={{
                    marginTop: '16px',
                  }}
                />}
            </div>
          </form>
        </div>
      </div>
    );
  }
}

SubmitFeedback.defaultProps = {
  revertToOldDesign: false,
};
