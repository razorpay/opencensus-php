import { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'common/deprecated/withRouter';
import { bindActionCreators, compose } from 'redux';
import AsyncButton from 'react-async-button';
import { Field, reduxForm, formValueSelector } from 'redux-form';
import ModalHeader from 'common/ui/ModalHeader';
import * as ModalActions from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

const selector = formValueSelector('uploadBatch');
class ProceedFormFields extends Component {
  onSubmitClick = (props) => {
    const prom = new Promise(() => {
      const additionalFormFields = {};

      additionalFormFields.sms_notify = props.sms_notify === true ? '1' : '0';
      additionalFormFields.email_notify = props.email_notify === true ? '1' : '0';
      additionalFormFields.draft = '0'; // Implicitly sending draft = '0'

      this.props
        .submitUploadBatch(additionalFormFields)
        .then(() => {
          this.props.closeModal();
          this.props.showNotification({
            type: 'success',
            message: 'Successful',
          });
          this.props.history.push(this.props.closeUrl);
        })
        .catch(({ errors }) => {
          this.props.closeModal();
          this.props.showNotification({
            type: 'error',
            message: errors,
          });
        });
    });

    return prom;
  };

  render() {
    const { handleSubmit } = this.props;

    return (
      <div className="proceed-upload-modal">
        <ModalHeader title="Batch Upload" onCloseClick={this.props.closeModal} />
        <form className="form-horizontal">
          <div className="modal-body">
            <div>
              <p>Send link and payment instructions to...</p>

              <div className="rzpCheckbox">
                <Field name="sms_notify" id="sms_notify" component="input" type="checkbox" />
                <label htmlFor="sms_notify" className="icon i-check">
                  Sms Notify
                </label>
              </div>

              <div className="rzpCheckbox">
                <Field name="email_notify" id="email_notify" component="input" type="checkbox" />
                <label htmlFor="email_notify" className="icon i-check">
                  Email Notify
                </label>
              </div>
            </div>

            <div>
              A <b>payment link</b> will also be created.
            </div>

            <div className="Modal__actions">
              <AsyncButton
                type="submit"
                className="btn btn-primary btn-block btn-lg"
                text="Submit"
                pendingText="Submitting..."
                onClick={handleSubmit(this.onSubmitClick)}
              />
            </div>
          </div>
        </form>
      </div>
    );
  }
}

const mapStateToProps = (state) => {
  return {
    sms_notify: selector(state, 'sms_notify'),
    email_notify: selector(state, 'email_notify'),
  };
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators({ ...ModalActions, showNotification }, dispatch);

export default compose(
  withRouter,
  connect(mapStateToProps, mapDispatchToProps),
  reduxForm({
    form: 'uploadBatch',
  }),
)(ProceedFormFields);
