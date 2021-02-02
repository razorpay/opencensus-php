import React, { useState } from 'react';
import RTracking from 'react-tracking';
import Input, { Description } from 'common/new-ui/Input';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import { merchantFetch } from 'merchant/utils/ajax';
import CopyOtpInput from './OtpInput';

const Error = ({ text }) => {
  return <div class="e-aadhar-error">{text}</div>;
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

  const updateOtpValue = (otpValue) => {
    setOtp(otpValue);
    setError('');
    setWrongOtp(false);
    trackEvent(window.rzpQ.onbr().initiated('kyc.e-aadhar_OTP'));
  };

  const updateSecurityPin = (pinValue) => {
    setPin(pinValue);
    trackEvent(window.rzpQ.onbr().initiated('kyc.e-aadhar_passcode'));
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
  };

  const generateCaptcha = () => {
    setError('');
    setIsOtpGenerated(false);
    setWrongOtp(false);
    merchantFetch({
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
      });
  };

  const generateOTP = () => {
    const body = {
      aadhaar_number: inputValue.aadhar_number,
      captcha: inputValue.captcha,
    };
    merchantFetch({
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
      })
      .catch((err) => {
        if (!err.success) {
          setError(err.errors[0]);
          setCaptcha({});
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
    merchantFetch({
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
          if (res.data.error_code === 'OTP_LIMIT_EXCEEDED') {
            setIsOtpGenerated(false);
            setCaptcha({});
            setPin('');
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
      })
      .catch((err) => {
        if (!err.success) {
          setError(err.errors[0]);
          setIsOtpGenerated(false);
          setCaptcha({});
        }
        trackEvent(
          window.rzpQ.onbr().initiated('kyc.e-aadhar_OTP_submit', {
            error_code: err.errors[0],
            trigger: true,
          }),
        );
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
            <>
              <Input
                name="aadhar_number"
                type="text"
                class="Input--small Input--vTop is-mature"
                label="Aadhar Verification"
                placeholder="Enter 12 digit Aadhar Number"
                onChange={handleOnChange}
                validator={(value) => {
                  if (value && value.length < 12) {
                    return 'Aadhar number should be of 12 digits';
                  }
                }}
                propagatedError={
                  error === 'MOBILE_NOT_LINKED'
                    ? 'Aadhar is not linked to any mobile number'
                    : error === 'INVALID_AADHAAR_NUMBER'
                    ? 'Aadhar number is invalid'
                    : ''
                }
                disabled={!hasMobileLinked}
                onFocus={() => {
                  trackEvent(
                    window.rzpQ.onbr().initiated('kyc.e-aadhar', {
                      interaction_aadhar: true,
                    }),
                  );
                }}
              />

              {(!captcha.captcha_image ||
                error === 'INVALID_SESSION_ID' ||
                error === 'MOBILE_NOT_LINKED') && (
                <>
                  <div className="Input-content">
                    <Button.Secondary
                      type="button"
                      className={
                        inputValue.aadhar_number
                          ? inputValue.aadhar_number.length > 11 && error !== 'MOBILE_NOT_LINKED'
                            ? 'e-aadhar-btn'
                            : ''
                          : ''
                      }
                      children="Verify With OTP >"
                      onClick={generateCaptcha}
                      disabled={
                        inputValue.aadhar_number
                          ? inputValue.aadhar_number.length < 12 || error === 'MOBILE_NOT_LINKED'
                          : true
                      }
                      style={{ boxShadow: 'none' }}
                    />
                    {error === 'INVALID_SESSION_ID' ? (
                      <Error text="The session has been timed out. Please start again" />
                    ) : error === 'OTP_LIMIT_EXCEEDED' ? (
                      <Error text="You have exceeded the maximum attempts to submit OTP. Please try again" />
                    ) : error.includes('Internal Server Error') || error === 'invalid_argument' ? (
                      <Error text="Something went wrong. Please try again" />
                    ) : null}
                    <Description
                      className={`e-aadhar-desc ${!hasMobileLinked && 'Input--disabled'}`}
                      text={
                        <>
                          By verifying, you consent to share your Aadhar details with Razorpay for
                          KYC and you agree with the{' '}
                          <a
                            href="https://razorpay.com/privacy/"
                            target="_blank"
                            onClick={() => {
                              trackEvent(window.rzpQ.onbr().initiated('kyc.e-aadhar_consent_link'));
                            }}
                          >
                            privacy policy
                          </a>
                        </>
                      }
                    />
                  </div>
                  <div className="Input-content e-aadhar-seprator" />
                </>
              )}
              {captcha.captcha_image &&
                !isOtpGenerated &&
                error !== 'INVALID_SESSION_ID' &&
                error !== 'MOBILE_NOT_LINKED' && (
                  <div className="captcha-container">
                    <div className="Input-content">
                      <img
                        src={`data:image/jpeg;base64, ${captcha.captcha_image}`}
                        alt="E-Aadhar captcha"
                        className="e-aadhar-captcha-img"
                      />
                      <img
                        src="/dist/css/assets/onboarding/resend.svg"
                        className="e-aadhar-resend"
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
                      }}
                    />
                    <div className="Input-content pin">
                      <CopyOtpInput
                        heading=""
                        autoFocus={false}
                        onChange={updateSecurityPin}
                        otpSize="4"
                      />
                    </div>
                    <Description
                      className="Input-content pin-desc"
                      text="Create any 4 digit pin to secure your Aadhar details with us"
                    />
                    <div className="Input-content">
                      <div className="e-aadhar-seprator" />
                      <Button.Secondary
                        type="button"
                        className={inputValue.captcha && pin.length === 4 ? 'e-aadhar-btn' : ''}
                        children="Send OTP"
                        onClick={generateOTP}
                        disabled={!inputValue.captcha || pin.length !== 4}
                        style={{ boxShadow: 'none' }}
                      />
                    </div>
                  </div>
                )}
              {!isOtpGenerated && (
                <>
                  <Input.Check
                    onChange={handleMoblieLinkedOnChange}
                    defaultValue={hasMobileLinked ? '0' : '1'}
                    fieldLabel="My Aadhar is not linked with my mobile number"
                  />
                  {!hasMobileLinked && (
                    <Description
                      className="Input-content e-aadhar-desc"
                      text="You can continue without submitting e - aadhar. KYC review might take a little longer"
                    />
                  )}
                </>
              )}
              {isOtpGenerated && !isValidOtp && error !== 'INVALID_SESSION_ID' && (
                <div className="otp-container">
                  <div className="Input-content otp">
                    <CopyOtpInput
                      heading="OTP has been sent to the number linked with Aadhar"
                      onComplete={updateOtpValue}
                      onChange={updateOtpValue}
                      wrong={wrongOtp}
                    />
                    {wrongOtp && <Error text="Invalid OTP. Try again" />}
                    <p class="m-t m-b">
                      Didn’t receive an OTP?{' '}
                      <AsyncBtn.Transparent
                        pendingState="Sending OTP..."
                        onClick={() => {
                          generateCaptcha();
                          setPin('');
                        }}
                        class="m-l"
                        showLoader={false}
                      >
                        Start again
                      </AsyncBtn.Transparent>
                    </p>
                  </div>

                  <div className="Input-content">
                    <div className="e-aadhar-seprator" />
                    <Button.Secondary
                      type="button"
                      className={otp.length === 6 && 'e-aadhar-btn'}
                      children="Submit"
                      onClick={verifyOTP}
                      disabled={otp.length !== 6}
                      style={{ boxShadow: 'none' }}
                    />
                  </div>
                </div>
              )}
            </>
          ) : (
            <div className="Input Input--small Input--vTop is-mature">
              <div className="Input-label">Aadhar Verification</div>
              <div className="Input-desc Input-content e-aadhar-provider-error">
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
