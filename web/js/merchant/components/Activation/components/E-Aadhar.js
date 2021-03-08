import React, { useState } from 'react';
import RTracking from 'react-tracking';
import Input, { Description } from 'common/new-ui/Input';
import { AsyncBtn } from 'common/new-ui/Button';
import { merchantFetch } from 'merchant/utils/ajax';
import OtpInput from 'common/new-ui/Input/OtpInput';
import { classList } from 'common/utils/rzp-utils';
import analyticsService from '@commander/services/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

const Error = ({ text }) => {
  return <div className="e-aadhar__error">{text}</div>;
};

const EAadhar = ({ aadharStatus, isAadharLinked, mobileLinkedOnChange, tracking }) => {
  const [inputValue, setInputValue] = useState({});
  const [otp, setOtp] = useState('');
  const [pin, setPin] = useState('');
  const [error, setError] = useState('');
  const [captcha, setCaptcha] = useState({});
  const [wrongOtp, setWrongOtp] = useState(false);
  const [isOtpGenerated, setIsOtpGenerated] = useState(false);
  const [isValidOtp, setIsValidOtp] = useState(false);
  const [hasMobileLinked, setHasMobileLinked] = useState(isAadharLinked);

  const trackEvent = tracking.trackEvent;

  const commenProperties = {
    properties: {
      location: 'Activation page',
      ...getCommonAnalyticsProperties(window.rzp_user),
    },
  };

  const updateOtpValue = (otpValue) => {
    setOtp(otpValue);
    setError('');
    setWrongOtp(false);
    trackEvent(window.rzpQ.onbr().initiated('kyc.e-aadhar_OTP'));
    analyticsService.track({
      objectName: 'kyc.e-aadhar OTP',
      actionName: 'Type',
      screen: 'Type OTP on Activation page',
      ...commenProperties,
    });
  };

  const updateSecurityPin = (pinValue) => {
    setPin(pinValue);
    trackEvent(window.rzpQ.onbr().initiated('kyc.e-aadhar_passcode'));
    analyticsService.track({
      objectName: 'kyc.e-aadhar passcode',
      actionName: 'type',
      screen: 'KYC Passcode type on Activation page',
      ...commenProperties,
    });
  };

  const handleOnChange = ({ target }) => {
    setError('');
    const value = target.value;
    const name = target.name;
    setInputValue({ ...inputValue, [name]: value });
  };

  const handleMoblieLinkedOnChange = ({ target }) => {
    setHasMobileLinked(target.value === '0');
    setError('');
    setCaptcha({});
    setInputValue({});
    document.getElementsByName('aadhar_number')[0].value = '';
    mobileLinkedOnChange(target.value !== '1');
    trackEvent(
      window.rzpQ.onbr().initiated('kyc.mobile_not_linked', {
        checked: target.value === '1',
      }),
    );
    analyticsService.track({
      objectName: 'kyc.mobile not linked',
      actionName: 'click on checkbox',
      screen: 'KYC on Activation page',
      ...commenProperties,
    });
  };

  const handleBackAction = () => {
    setCaptcha({});
    setInputValue({});
    setPin('');
    document.getElementsByName('aadhar_number')[0].value = '';
    trackEvent(window.rzpQ.onbr().initiated('kyc.e-aadhar_reset'));
    analyticsService.track({
      objectName: 'kyc.aadhar reset',
      actionName: 'click on reset process',
      screen: 'Captcha screen on Activation page',
      ...commenProperties,
    });
  };

  const generateCaptcha = () => {
    if (inputValue.aadhar_number && inputValue.aadhar_number.length !== 12) {
      setError('invalid_aadhar_length');
      return;
    }
    setError('');
    setIsOtpGenerated(false);
    setWrongOtp(false);
    return merchantFetch({
      url: 'bvs/dashboard/twirp/platform.bvs.probe.v1.ProbeAPI/AadhaarGetCaptcha',
      method: 'POST',
      data: JSON.stringify({}),
    })
      .then((res) => {
        if (res.success && !res.data.error_code) {
          setCaptcha(res.data);
        }
        if (res.data.error_code) {
          setError(res.data.error_code);
          if (res.data.error_code === 'NO_PROVIDER_ERROR') {
            mobileLinkedOnChange(false);
          }
        }
        trackEvent(
          window.rzpQ.onbr().initiated('kyc.e-aadhar_get_code', {
            trigger: true,
            error_code: res.data?.error_code ? res.data?.error_code : null,
          }),
        );
        analyticsService.track({
          objectName: 'kyc.e-aadhar get code',
          actionName: 'generate captcha',
          screen: 'Verify with OTP on Activation page',
          ...commenProperties,
        });
      })
      .catch((err) => {
        if (!err.success) {
          setError(err.errors[0]);
        }
        trackEvent(
          window.rzpQ.onbr().initiated('kyc.e-aadhar_get_code', {
            trigger: true,
            error_code: err.errors[0],
          }),
        );
        analyticsService.track({
          objectName: 'kyc.e-aadhar get code',
          actionName: 'generate captcha Error',
          screen: 'Verify with OTP on Activation page',
          ...commenProperties,
        });
      });
  };

  const generateOTP = () => {
    const body = {
      aadhaar_number: inputValue.aadhar_number,
      captcha: inputValue.captcha,
    };
    return merchantFetch({
      url: 'bvs/dashboard/twirp/platform.bvs.probe.v1.ProbeAPI/AadhaarVerifyCaptchaAndSendOtp',
      method: 'POST',
      data: body,
    })
      .then((res) => {
        if (res.success && !res.data.error_code) {
          setIsOtpGenerated(res.data.is_success);
          trackEvent(
            window.rzpQ.onbr().initiated('kyc.e-aadhar_send_otp', {
              trigger: true,
            }),
          );
        }
        if (res.data.error_code) {
          setError(res.data.error_code);
          if (
            res.data.error_code === 'INVALID_AADHAAR_NUMBER' ||
            res.data.error_code === 'INVALID_CAPTCHA'
          ) {
            setInputValue({ ...inputValue, captcha: '' });
            document.getElementsByName('captcha')[0].value = '';
          }
          if (res.data.error_code === 'NO_PROVIDER_ERROR') {
            mobileLinkedOnChange(false);
          }
          if (res.data.error_code === 'INTERNAL_SERVER_ERROR') {
            setInputValue({ ...inputValue, captcha: '' });
            setCaptcha({});
            setPin('');
          }
        }
        if (res.data.code) {
          setError(res.data.code);
          if (res.data.code === 'invalid_argument') {
            setCaptcha({});
          }
          trackEvent(
            window.rzpQ.onbr().initiated('kyc.e-aadhar_send_otp', {
              error_code: res.data.code,
              trigger: true,
            }),
          );
        } else {
          trackEvent(
            window.rzpQ.onbr().initiated('kyc.e-aadhar_send_otp', {
              error_code: res.data?.error_code ? res.data.error_code : null,
              trigger: true,
            }),
          );
        }
        analyticsService.track({
          objectName: 'kyc.e-aadhar send otp',
          actionName: 'send OTP',
          screen: 'Send OTP button on Activation page',
          ...commenProperties,
        });
      })
      .catch((err) => {
        if (!err.success) {
          setError(err.errors[0]);
          setCaptcha({});
          if (err.errors[0] === 'INVALID_SESSION_ID') {
            setInputValue({ ...inputValue, captcha: '' });
            setPin('');
          }
        }
        trackEvent(
          window.rzpQ.onbr().initiated('kyc.e-aadhar_send_otp', {
            error_code: err.errors[0],
            trigger: true,
          }),
        );
      });
  };

  const verifyOTP = () => {
    const body = {
      otp: otp,
      captcha: inputValue.captcha,
      file_password: pin,
    };
    return merchantFetch({
      url: 'bvs/dashboard/twirp/platform.bvs.probe.v1.ProbeAPI/AadhaarSubmitOtp',
      method: 'POST',
      data: body,
    })
      .then((res) => {
        if (res.success && !res.data.error_code) {
          setIsValidOtp(res.data.is_valid);
          mobileLinkedOnChange(true);
        }
        if (res.data.error_code) {
          setError(res.data.error_code);
          if (res.data.error_code === 'INCORRECT_OTP') {
            setWrongOtp(true);
          }
          if (
            res.data.error_code === 'OTP_LIMIT_EXCEEDED' ||
            res.data.error_code === 'INTERNAL_SERVER_ERROR'
          ) {
            setInputValue({ ...inputValue, captcha: '' });
            setIsOtpGenerated(false);
            setCaptcha({});
            setPin('');
            setOtp('');
          }
          if (res.data.error_code === 'NO_PROVIDER_ERROR') {
            mobileLinkedOnChange(false);
          }
        }
        if (res.data.code) {
          setError(res.data.code);
          if (res.data.code === 'invalid_argument') {
            setIsOtpGenerated(false);
            setCaptcha({});
          }
          trackEvent(
            window.rzpQ.onbr().initiated('kyc.e-aadhar_OTP_submit', {
              error_code: res.data.code,
              trigger: true,
            }),
          );
        } else {
          trackEvent(
            window.rzpQ.onbr().initiated('kyc.e-aadhar_OTP_submit', {
              error_code: res.data?.error_code ? res.data.error_code : null,
              trigger: true,
            }),
          );
        }
        analyticsService.track({
          objectName: 'kyc.e-aadhar OTP submit',
          actionName: 'OTP verified successfully',
          screen: 'Submit OTP on Activation page',
          ...commenProperties,
        });
      })
      .catch((err) => {
        if (!err.success) {
          setError(err.errors[0]);
          setIsOtpGenerated(false);
          setCaptcha({});
          if (err.errors[0] === 'INVALID_SESSION_ID') {
            setInputValue({ ...inputValue, captcha: '' });
            setPin('');
            setOtp('');
          }
        }
        trackEvent(
          window.rzpQ.onbr().initiated('kyc.e-aadhar_OTP_submit', {
            error_code: err.errors[0],
            trigger: true,
          }),
        );
        analyticsService.track({
          objectName: 'kyc.e-aadhar OTP submit',
          actionName: 'OTP verify failed',
          screen: 'Submit OTP on Activation page',
          ...commenProperties,
        });
      });
  };

  return (
    <>
      {aadharStatus || isValidOtp ? (
        <>
          <Input
            type="text"
            class="Input--small"
            label="Aadhar Verification"
            defaultValue="XXXXXXXXXXXX"
            style={{ border: '1px solid rgba(31, 137, 14, 0.54)' }}
            addonAfter={<i className="i i-check text-success" />}
            readOnly={true}
          />
          <div className="Input-content e-aadhar-success-text">
            We have recieved your Aadhar details
          </div>
        </>
      ) : (
        <div style={{ marginBottom: '45px' }}>
          {error !== 'NO_PROVIDER_ERROR' ? (
            <div className="e-aadhar">
              <Input
                name="aadhar_number"
                type="text"
                class="Input--small Input--vTop is-mature"
                label="Aadhar Verification"
                placeholder="Enter 12 digit Aadhar Number"
                onChange={handleOnChange}
                propagatedError={
                  error === 'MOBILE_NOT_LINKED'
                    ? 'Aadhar is not linked to any mobile number'
                    : error === 'INVALID_AADHAAR_NUMBER'
                    ? 'Aadhar number is invalid'
                    : error === 'invalid_aadhar_length'
                    ? 'Aadhar number should be of 12 digits'
                    : ''
                }
                disabled={!hasMobileLinked}
                onFocus={() => {
                  trackEvent(
                    window.rzpQ.onbr().initiated('kyc.e-aadhar', {
                      interaction_aadhar: true,
                    }),
                  );
                  analyticsService.track({
                    objectName: 'kyc.e-aadhar',
                    actionName: 'focus',
                    screen: 'Activation page',
                    ...commenProperties,
                  });
                }}
              />

              {(!captcha.captcha_image ||
                error === 'INVALID_SESSION_ID' ||
                error === 'MOBILE_NOT_LINKED') && (
                <>
                  <div className="Input-content">
                    <AsyncBtn.Secondary
                      type="button"
                      className={hasMobileLinked ? 'e-aadhar__btn' : ''}
                      children="Verify With OTP >"
                      onClick={generateCaptcha}
                      disabled={!hasMobileLinked}
                      style={{ boxShadow: 'none' }}
                      pendingState="Verify With OTP"
                    />
                    {error === 'INVALID_SESSION_ID' ? (
                      <Error text="The session has been timed out. Please start again" />
                    ) : error === 'OTP_LIMIT_EXCEEDED' ? (
                      <Error text="You have exceeded the maximum attempts to submit OTP. Please try again" />
                    ) : error.includes('Internal Server Error') ||
                      error === 'invalid_argument' ||
                      error === 'INTERNAL_SERVER_ERROR' ? (
                      <Error text="Something went wrong. Please try again" />
                    ) : null}
                    <Description
                      className={classList(
                        'e-aadhar__desc e-aadhar__otp-helptext',
                        !hasMobileLinked && 'Input--disabled',
                      )}
                      text="OTP will be sent to mobile number linked to your Aadhar"
                    />
                    <Description
                      className={classList(
                        'e-aadhar__desc e-aadhar__consent',
                        !hasMobileLinked && 'Input--disabled',
                      )}
                      text={
                        <>
                          By verifying, you consent to share your Aadhar details with Razorpay for
                          KYC and you agree with the{' '}
                          <a
                            href="https://razorpay.com/privacy/"
                            target="_blank"
                            onClick={() => {
                              trackEvent(window.rzpQ.onbr().initiated('kyc.e-aadhar_consent_link'));
                              analyticsService.track({
                                objectName: 'kyc.e-aadhar consent link',
                                actionName: 'click on privacy policy (e-aadhar)',
                                screen: 'KYC on Activation page',
                                ...commenProperties,
                              });
                            }}
                          >
                            privacy policy
                          </a>
                        </>
                      }
                    />
                  </div>
                  <div className="Input-content e-aadhar__seprator" />
                  <Input.Check
                    onChange={handleMoblieLinkedOnChange}
                    defaultValue={hasMobileLinked ? '0' : '1'}
                    fieldLabel="My Aadhar is not linked with my mobile number"
                    className="e-aadhar__not-linked-checkbox"
                  />
                  {!hasMobileLinked && (
                    <Description
                      className="Input-content e-aadhar__not-linked-text"
                      text="Your KYC review will be delayed by two weeks if you don't verify your aadhar. It usually takes just 2-3 days with verified aadhar"
                    />
                  )}
                </>
              )}
              {captcha.captcha_image &&
                !isOtpGenerated &&
                error !== 'INVALID_SESSION_ID' &&
                error !== 'MOBILE_NOT_LINKED' && (
                  <div className="captcha-screen">
                    <div className="Input-content">
                      <img
                        src={`data:image/jpeg;base64,${captcha.captcha_image}`}
                        alt="E-Aadhar captcha"
                        className="captcha-screen__captcha-img"
                      />
                      <img
                        src="/dist/css/assets/onboarding/resend.svg"
                        className="captcha-screen__resend"
                        onClick={generateCaptcha}
                      />
                    </div>
                    <Input
                      name="captcha"
                      type="text"
                      class="Input--small Input--vTop is-mature"
                      placeholder="Enter Code"
                      onChange={handleOnChange}
                      description={
                        error !== 'INVALID_CAPTCHA' && 'Enter the code shown in the above image'
                      }
                      propagatedError={
                        error === 'INVALID_CAPTCHA' &&
                        'Code didn’t match, please enter the new code'
                      }
                      onFocus={() => {
                        trackEvent(window.rzpQ.onbr().initiated('kyc.e-aadhar_code'));
                        analyticsService.track({
                          objectName: 'kyc.e-aadhar code',
                          actionName: 'focus on enter captcha',
                          screen: 'Entering captch on Activation page',
                          ...commenProperties,
                        });
                      }}
                    />
                    <div className="Input-content captcha-screen__pin">
                      <OtpInput
                        heading=""
                        autoFocus={false}
                        onChange={updateSecurityPin}
                        otpSize="4"
                      />
                    </div>
                    <Description
                      className="Input-content captcha-screen__pin-desc"
                      text="Create any 4 digit pin to secure your Aadhar details with us"
                    />
                    <div className="Input-content">
                      <div className="e-aadhar__seprator" />
                      <div className="captcha-screen__btn-container">
                        <div className="btn__back" onClick={handleBackAction}>
                          Back
                        </div>
                        <AsyncBtn.Secondary
                          type="button"
                          className={
                            inputValue.captcha && pin.length === 4 && inputValue.aadhar_number
                              ? 'e-aadhar__btn'
                              : ''
                          }
                          children="Send OTP"
                          onClick={generateOTP}
                          disabled={
                            !inputValue.captcha || pin.length !== 4 || !inputValue.aadhar_number
                          }
                          style={{ boxShadow: 'none' }}
                          pendingState="Sending OTP"
                        />
                      </div>
                    </div>
                  </div>
                )}
              {isOtpGenerated && !isValidOtp && error !== 'INVALID_SESSION_ID' && (
                <div className="otp-screen">
                  <div className="Input-content otp-screen__otp">
                    <OtpInput
                      heading="OTP has been sent to the number linked with Aadhar"
                      onComplete={updateOtpValue}
                      onChange={updateOtpValue}
                      wrong={wrongOtp}
                    />
                    {wrongOtp && <Error text="Invalid OTP. Try again" />}
                    <p className="m-t m-b resend-otp">
                      Didn’t receive an OTP?{' '}
                      <AsyncBtn.Transparent
                        pendingState="Sending OTP..."
                        onClick={() => {
                          generateCaptcha();
                          setPin('');
                          setOtp('');
                        }}
                        class="m-l"
                        showLoader={false}
                      >
                        Start again
                      </AsyncBtn.Transparent>
                    </p>
                  </div>

                  <div className="Input-content">
                    <div className="e-aadhar__seprator" />
                    <AsyncBtn.Secondary
                      type="button"
                      className={
                        otp.length === 6 && inputValue.aadhar_number ? 'e-aadhar__btn' : ''
                      }
                      children="Submit"
                      onClick={verifyOTP}
                      disabled={otp.length !== 6 || !inputValue.aadhar_number}
                      style={{ boxShadow: 'none' }}
                      pendingState="Submitting"
                    />
                  </div>
                </div>
              )}
            </div>
          ) : (
            <div className="Input Input--small Input--vTop is-mature">
              <div className="Input-label">Aadhar Verification</div>
              <div className="Input-content e-aadhar-provider-error">
                The Aadhar database doesn’t seem to be working at the moment. You can proceed
                without Aadhar verification
              </div>
            </div>
          )}
        </div>
      )}
    </>
  );
};

export default RTracking(() => {
  return window.rzpQ.component('EAadhar');
})(EAadhar);
