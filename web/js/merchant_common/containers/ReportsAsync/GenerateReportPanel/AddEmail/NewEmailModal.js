import React, { useState } from 'react';
import InputField from 'common/ui/Forms/InputField';
import { required, isEmail } from 'common/utils/validators';
import { showNotification as fnShowNotification } from 'merchant_common/reducers/notifications';
import { closeModal as fnCloseModal } from 'merchant_common/reducers/modals';
import { connect } from 'react-redux';
import { compose } from 'redux';
import AsyncButton from 'react-async-button';
import { reduxForm, Field } from 'redux-form';
import { addEmail } from './services';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

const EmailModal = ({
  screen,
  onSubmit,
  handleSubmit,
  otpAuthToken,
  showNotification,
  closeModal,
  onClose,
}) => {
  const [isSubmitting, setIsSubmitting] = useState(false);

  const _onSubmit = ({ email }) => {
    analyticsTrack({
      objectName: 'add email submit',
      actionName: 'clicked',
      screen,
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    if (!email || !isEmail(email)) {
      showNotification({
        type: 'error',
        message: 'Invalid email',
      });
      return;
    }

    setIsSubmitting(true);
    addEmail({ email, otpAuthToken })
      .then((res) => {
        setIsSubmitting(false);
        if (res.success) {
          analyticsTrack({
            objectName: 'add email',
            actionName: 'result',
            screen,
            properties: {
              result: 'Success',
              ...getCommonAnalyticsProperties(window.rzp_user),
            },
          });
          onSubmit({ email });
        }
      })
      .catch((err) => {
        analyticsTrack({
          objectName: 'add email',
          actionName: 'result',
          screen,
          properties: {
            result: 'Failure',
            failureMessage: err.errors && err.errors.length ? `${err.errors[0]}` : null,
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
        showNotification({
          type: 'error',
          message:
            err.errors && err.errors.length ? err.errors[0] : 'Some error occured. Please refresh',
        });
        setIsSubmitting(false);
      });
  };
  const onPopupClose = () => {
    if (onClose) onClose();
    closeModal();
  };

  return (
    <div className="add-email-modal-content">
      <div className="merchant-heading">
        {'Add your email address'}
        <button type="button" className="close" onClick={onPopupClose}>
          <i className="i i-close" />
        </button>
      </div>
      <div className="merchant-note email-merchant-note">
        We will use this to send important updates and alerts. You can also use your email to
        recover your account in case you get locked out.
      </div>
      <form onSubmit={handleSubmit(_onSubmit)}>
        <div className="form-group">
          <label className="label-required">Email Address</label>
          <Field
            component={InputField}
            type="email"
            name="email"
            className="form-control"
            validate={required()}
          />
        </div>
        <AsyncButton
          type="submit"
          className="btn btn-primary btn-block"
          onClick={handleSubmit(_onSubmit)}
          text={isSubmitting ? 'Verifying...' : 'Add Email'}
          disabled={isSubmitting}
        />
      </form>
    </div>
  );
};

export default compose(
  connect(null, { showNotification: fnShowNotification, closeModal: fnCloseModal }),
  reduxForm({
    form: 'addEmail',
  }),
)(EmailModal);
