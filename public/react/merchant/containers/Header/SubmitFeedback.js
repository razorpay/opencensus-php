import { Component, PropTypes } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm } from 'redux-form';
import AsyncButton from 'react-async-button';
import InputField from 'rzp/ui/Forms/InputField';
import ModalHeader from 'rzp/ui/ModalHeader';
import { required } from 'rzp/utils/validators';
import { closeModal } from 'rzp/modules/modals';
import { showNotification } from 'rzp/modules/notifications';
import { enableOrDisableNewui } from 'merchant/modules/session';

@connect(state => state.session, {
  closeModal,
  showNotification,
  enableOrDisableNewui,
})
@reduxForm({
  form: 'submitFeedback',
})
export default class SubmitFeedback extends Component {
  _submit = props => {
    const Smooch = window.Smooch;
    if (Smooch) {
      return Smooch.sendMessage(props.message)
        .then(() => {
          this.props.showNotification({
            type: 'success',
            message: 'Submitted! Thanks for your feedback.',
          });
        })
        .catch(err => {
          debugger;
        });
    }

    this.props.showNotification({
      type: 'error',
      message: 'Smooch not loaded!!!',
    });

    return Promise.reject();
  };

  submit = props => {
    return this._submit(props).then(() => {
      this.props.closeModal();
    });
  };

  revert = () => {
    return this.props.enableOrDisableNewui({
      user: this.props.user,
      disable: true,
    });
  };

  submitAndRevert = props => {
    return this._submit(props).then(() => {
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
                {revertToOldDesign
                  ? <Field
                      name="message"
                      component="textarea"
                      class="form-control"
                      rows={10}
                      placeholder="This will help us learn and fix issues"
                      autoFocus={true}
                    />
                  : <Field
                      name="message"
                      component={InputField}
                      tagName="textarea"
                      class="form-control"
                      autoFocus={true}
                      rows={10}
                      placeholder="We would love to know your thoughts"
                      validate={required()}
                    />}
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
