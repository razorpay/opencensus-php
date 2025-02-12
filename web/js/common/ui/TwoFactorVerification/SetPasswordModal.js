import React, { useState, useEffect } from 'react';
import ModalHeader from 'common/ui/ModalHeader';
import InputField from 'common/ui/Forms/InputField';
import AsyncButton from 'react-async-button';
import { connect } from 'react-redux';
import { compose } from 'redux';
import { showNotification as fnShowNotification } from 'merchant_common/reducers/notifications';
import { Field, reduxForm } from 'redux-form';
import { required } from 'common/utils/validators';
import {
  setPassword as setPasswordReducer,
  checkPassword as checkPasswordReducer,
} from 'merchant/reducers/profile';
import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import { Modules } from 'common/constant/enums';

const SetPasswordModal = ({
  onComplete,
  onClose,
  customTitle,
  customMessage,
  buttonText,
  buttonPendingText,
  setPassword,
  checkPassword,
  showNotification,
  valid,
  handleSubmit,
  isNewAccountAndSettingsPage = false,
}) => {
  const [inputPassword, setInputPassword] = useState(null);
  const [inputConfirmPassword, setInputConfirmPassword] = useState(null);

  const onChange = (value, confirm) => {
    if (confirm) {
      setInputConfirmPassword(value);
    } else {
      setInputPassword(value);
    }
  };
  const makeValidator = (truthyFn, defaultMessage) => (message = defaultMessage) => (value) =>
    truthyFn(value) ? undefined : message;

  const length = makeValidator(
    (input_password) => input_password.length >= 8,
    'Password must be minimum 8 characters',
  );
  const passwordValidator = makeValidator(
    (input_password) => /[a-z]/i.test(input_password) && /[0-9]/g.test(input_password),
    'Password must contain atleast one letter and one number',
  );
  const screen = isNewAccountAndSettingsPage ? Modules.AccountAndSettings : Modules.MyAccount;

  const onSubmit = (data) => {
    if (data.password !== data.password_confirmation) {
      return showNotification({
        type: 'error',
        message: 'Passwords do not match',
      });
    }

    analyticsTrackWithUserInfo({
      objectName: '2fa set password submit',
      actionName: 'clicked',
      screen,
    });

    return setPassword(data)
      .then(() => {
        checkPassword();
        analyticsTrackWithUserInfo({
          objectName: '2fa set password',
          actionName: 'result',
          screen,
          properties: {
            result: 'Success',
          },
        });
        showNotification({
          type: 'success',
          message: 'Password set successfully.',
        });
        onComplete?.(inputPassword);
      })
      .catch((err) => {
        analyticsTrackWithUserInfo({
          objectName: '2fa set password',
          actionName: 'result',
          screen,
          properties: {
            result: 'Failure',
            failureReason: err.errors[0],
          },
        });
        showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  };
  const _onClose = () => {
    analyticsTrackWithUserInfo({
      objectName: '2fa set password popup close',
      actionName: 'clicked',
      screen,
    });
    onClose?.();
  };

  useEffect(() => {
    analyticsTrackWithUserInfo({
      objectName: '2fa set password popup',
      actionName: 'rendered',
      screen,
    });
  }, []);

  return (
    <div className="set-password-modal">
      <ModalHeader
        title={customTitle ? customTitle : `Setting up 2-step verification`}
        onCloseClick={_onClose}
      />
      <div className="modal-body">
        <p className="merchant-note">
          {customMessage
            ? customMessage
            : `Set a strong password for your Razorpay account. Now your Razorpay account will be more secure.`}
        </p>
        <form onSubmit={handleSubmit(onSubmit)}>
          <div className="form-group">
            <label htmlFor="password">Create a new password </label>
            <Field
              component={InputField}
              type="password"
              placeholder="New Password"
              name="password"
              className="form-control"
              onChange={(event, value) => onChange(value)}
              validate={[required(), length(), passwordValidator()]}
              autoFocus={true}
            />
          </div>
          <div className="form-group">
            <label htmlFor="password_confirmation">Confirm new password </label>
            <Field
              component={InputField}
              type="password"
              placeholder="Confirm New Password"
              name="password_confirmation"
              className="form-control"
              onChange={(event, value) => onChange(value, true)}
              validate={[required()]}
            />
            {valid && inputPassword === inputConfirmPassword ? (
              <small className="text-success">Both passwords match</small>
            ) : null}
          </div>
          <AsyncButton
            className="btn btn-primary btn-block"
            text={buttonText ? buttonText : 'Set Password'}
            pendingText={buttonPendingText ? buttonPendingText : 'Setting Password...'}
            disabled={!inputPassword}
            onClick={handleSubmit(onSubmit)}
          />
        </form>
      </div>
    </div>
  );
};

export default compose(
  connect(null, {
    showNotification: fnShowNotification,
    setPassword: setPasswordReducer,
    checkPassword: checkPasswordReducer,
  }),
  reduxForm({
    form: 'askPasswordForm',
  }),
)(SetPasswordModal);
