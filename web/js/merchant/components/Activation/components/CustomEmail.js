import React, { useEffect, useState } from 'react';
import rTracking from 'react-tracking';
import { connect } from 'react-redux';
import { compose } from 'redux';
import User from 'merchant/models/User';
import Input, { Description } from 'common/new-ui/Input';
import { AsyncBtn } from 'common/new-ui/Button';
import { OtpInput } from 'common/new-ui/Input/OtpInput';
import { isEmail } from 'common/utils/validators';
import { merchantFetch } from 'merchant/utils/ajax';
import { updateSession } from 'merchant/reducers/session';
import { trackEvents } from 'merchant/reducers/trackEvents';

const CustomEmail = ({
  contactEmail,
  contactName,
  emailVerified,
  isEmailNonMandatoryOnL1,
  updateSessions,
  postSuccessfulEmailVerify,
  setEnableAndDisableCheckbox,
  sendErrorMessageToSegment,
  activationMilestone,
  trackEventsAction,
  tracking,
  user,
  mode,
  setEnableAndDisableCheckboxOnL2,
  isChecked,
}) => {
  const [email, setEmail] = useState(contactEmail || '');
  const [error, setError] = useState('');
  const [otp, setOtp] = useState('');
  const [isOtpSend, setIsOtpSend] = useState(false);
  const [ischeck, setIsCheck] = useState(isChecked);
  const [token, setToken] = useState('');
  const [wrongOtp, setWrongOtp] = useState(false);
  const [isApiCalling, setIsApiCall] = useState(false);
  const [isEmailVerified, setIsEmailVerified] = useState(emailVerified);

  const onChangeHandler = ({ target }) => {
    const value = target.value;
    setEmail(value);
    setError('');
    setIsApiCall(false);
    if (value.length && isEmailNonMandatoryOnL1) {
      setEnableAndDisableCheckbox(false);
    } else if (value === '' && contactName && isEmailNonMandatoryOnL1) {
      setEnableAndDisableCheckbox(true);
    }
  };

  const sendOTP = () => {
    if (!isEmail(email)) {
      setError('Unable to send OTP. Kindly check the entered email');
      return;
    }
    setIsApiCall(true);
    // if email id not changed send send token in payload
    const payload = token && email === contactEmail ? { email, token } : { email };

    merchantFetch({
      url: 'merchant/activation/otp/send',
      method: 'POST',
      data: payload,
      mode: 'live',
    })
      .then((res) => {
        setIsApiCall(false);
        if (res?.data && res?.data?.token) {
          setToken(res.data.token);
          setIsOtpSend(true);
        }
      })
      .catch((err) => {
        setIsApiCall(false);
        if (err && Array.isArray(err.errors)) {
          setError(err.errors[0]);
        } else {
          setError('Please try again!!!');
        }
      });
  };

  const resetValue = () => {
    setEmail('');
    setIsOtpSend(false);
  };

  const verifyOTP = () => {
    const payload = { otp, token };
    trackEventsAction({
      objectName: 'Verify Email',
      actionName: 'Request',
      screen: 'KYC Contact Info',
      properties: {
        status: 'success',
      },
    });
    tracking.trackEvent(
      window.rzpQ.onbr().initiated('act.email_request', {
        status: 'success',
      }),
    );
    merchantFetch({ url: 'users/verify_email', method: 'POST', data: payload })
      .then((res) => {
        if (res?.data?.user?.email_verified) {
          const updateSelectedUserValue = {
            confirmed: res.data.user.confirmed,
            email: email || res.data.user.email,
            email_verified: res.data.user.email_verified,
            signup_via_email: res.data.user.signup_via_email,
          };
          const userData = new User({
            ...user,
            user: { ...user.user, ...updateSelectedUserValue },
            contact_email: email || res.data.user.email,
          });

          updateSessions({
            user: userData,
            mode,
          });
          const verifiedEmail = email || res.data.user.email;
          postSuccessfulEmailVerify({ contact_email: verifiedEmail });
          setIsEmailVerified(true);
          trackEventsAction({
            objectName: 'Verify Email',
            actionName: 'Result',
            screen: 'KYC Contact Info',
            properties: {
              status: 'success',
            },
          });
          tracking.trackEvent(
            window.rzpQ.onbr().initiated('act.email_result', {
              status: 'success',
            }),
          );
        }
      })
      .catch((err) => {
        if (err && Array.isArray(err.errors)) {
          setError(err.errors[0]);
        } else {
          setError('Please try again!!!');
        }
        setWrongOtp(true);
        setIsApiCall(false);
        trackEventsAction({
          objectName: 'Verify Email',
          actionName: 'Result',
          screen: 'KYC Contact Info',
          properties: {
            status: 'failure',
            errorMessage: err.errors[0],
          },
        });
        tracking.trackEvent(
          window.rzpQ.onbr().initiated('act.email_result', {
            status: 'failure',
            errorMessage: err.errors[0],
          }),
        );
      });
  };

  const updateOtpValue = (otpValue) => {
    setOtp(otpValue);
    setError('');
    setIsApiCall(false);
    setWrongOtp(false);
  };

  useEffect(() => {
    if (otp.length === 6) {
      trackEventsAction({
        objectName: 'Verify Email',
        actionName: 'clicked',
        screen: 'KYC Contact Info',
        properties: {
          optional: isEmailNonMandatoryOnL1 || user.isEmailNonMandatoryOnL2Form ? 'Yes' : 'NO',
        },
      });
      tracking.trackEvent(
        window.rzpQ.onbr().clicked('act.email', {
          optional: isEmailNonMandatoryOnL1 || user.isEmailNonMandatoryOnL2Form ? 'Yes' : 'NO',
        }),
      );
      verifyOTP();
      setIsApiCall(true);
    }
  }, [otp]);

  const VerifiedEmail = () => {
    return (
      <>
        <Input
          label="Contact Email"
          class="Input--small"
          defaultValue={contactEmail || email}
          style={{ border: '1px solid rgba(31, 137, 14, 0.54)' }}
          addonAfter={<i className="i i-check text-success" />}
          disabled={true}
          required={!isEmailNonMandatoryOnL1}
        />
        <div className="Input-content success-email">Email verified successfully</div>
      </>
    );
  };

  return (
    <div className="custom-email">
      {user.isEmailNonMandatoryOnL2Form && (
        <Input.Check
          fieldLabel="Send all important communication and account updates on email"
          extraClassName="Custom-input-space"
          onChange={({ target }) => {
            setEnableAndDisableCheckboxOnL2(!target.checked);
            if (!target.checked) {
              resetValue();
            }
            setIsCheck(target.checked);
          }}
          autoRender={true}
          checked={ischeck || isEmailVerified}
        />
      )}
      {(isChecked && ischeck) || !user.isEmailNonMandatoryOnL2Form ? (
        <div>
          {isEmailVerified ? (
            <VerifiedEmail />
          ) : !activationMilestone || user.isEmailNonMandatoryOnL2Form ? (
            <>
              <Input
                label="Contact Email"
                name="contact_email"
                type="email"
                value={email}
                onChange={onChangeHandler}
                placeholder="contact@email.com"
                className="Input--small is-mature"
                propagatedError={isOtpSend ? '' : error}
                autoRender={true}
                disabled={isOtpSend}
                description={
                  !isOtpSend && !user.isEmailNonMandatoryOnL2Form
                    ? 'All important communications and account updates will be sent to this email ID'
                    : ''
                }
                onBlur={(e) => {
                  sendErrorMessageToSegment(e, error);
                }}
                required={!isEmailNonMandatoryOnL1 || !user.isEmailNonMandatoryOnL2Form}
              />

              {!isOtpSend ? (
                <div className="Input-content">
                  <AsyncBtn.Secondary
                    type="button"
                    className="send-otp-btn"
                    children="Verify With OTP >"
                    onClick={sendOTP}
                    isPending={isApiCalling}
                    pendingState="Sending OTP"
                    disabled={!email}
                  />
                  <div className="email-seprator" />
                  <Description text="You email will not be saved till it’s verified." />
                </div>
              ) : (
                <div className="Input-content">
                  <Description text="6 digit OTP has been sent to your email ID" />
                  <div className="email-otp">
                    <OtpInput
                      heading=""
                      onComplete={updateOtpValue}
                      onChange={updateOtpValue}
                      wrong={wrongOtp}
                    />
                    {wrongOtp && <div className="email-error">{error}</div>}
                  </div>
                  <p className="m-t m-b resend-otp">
                    Didn’t receive an OTP?{' '}
                    <AsyncBtn.Transparent
                      pendingState="Sending OTP..."
                      onClick={() => {
                        setIsOtpSend(false);
                        setError('');
                        setWrongOtp(false);
                      }}
                      showLoader={false}
                    >
                      Start again
                    </AsyncBtn.Transparent>
                  </p>
                  <AsyncBtn.Secondary
                    type="button"
                    className={otp.length === 6 ? 'send-otp-btn' : ''}
                    children="Submit & Verify >"
                    isPending={isApiCalling}
                    disabled={otp.length !== 6}
                    pendingState="Verifying"
                  />
                </div>
              )}
            </>
          ) : null}
        </div>
      ) : null}
    </div>
  );
};

export default compose(
  connect(
    (state) => ({
      user: state.session.user,
      mode: state.session.mode,
    }),
    { updateSessions: updateSession, trackEventsAction: trackEvents },
  ),
  rTracking(() => window.rzpQ.component('CustomEmail')),
)(CustomEmail);
