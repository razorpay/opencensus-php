import React, { useState, useEffect } from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';
import Input, { Description } from 'common/new-ui/Input';
import { AsyncBtn } from 'common/new-ui/Button';
import { merchantFetch } from 'merchant/utils/ajax';
import { classList } from 'common/utils/rzp-utils';
import { analyticsTrack } from 'common/utils/analytics';
import * as EventActions from 'merchant/reducers/trackEvents';

const GetOtpScreen = ({
  analyticsProperties,
  isAadharLinked,
  mobileLinkedOnChange,
  activeTab,
  aadharNumber,
  captcha,
  setError,
  error,
  onChange,
  setScreen,
  setAadharNumber,
  setCaptchaValue,
  trackEvent,
  isStartAgain,
  setIsStartAgain,
  isAadharEkycMandatory,
  trackEvents,
  handleDownTimeError,
}) => {
  const [captchaImg, setCaptchaImg] = useState('');
  const [hasMobileLinked, setHasMobileLinked] = useState(isAadharLinked);

  const handleMoblieLinkedOnChange = () => {
    setHasMobileLinked(!hasMobileLinked);
    setError('');
    setAadharNumber('');
    setCaptchaValue('');
    mobileLinkedOnChange(!hasMobileLinked);
    trackEvent(
      window.rzpQ.onbr().initiated('kyc.mobile_not_linked', {
        checked: hasMobileLinked,
      }),
    );
    analyticsTrack({
      objectName: 'kyc.mobile not linked',
      actionName: 'click on checkbox',
      screen: 'KYC on Activation page',
      ...analyticsProperties,
    });
    trackEvents({
      objectName: 'Checkbox',
      actionName: 'Clicked',
      screen: 'home page',
      properties: {
        'Checkbox Label': 'My Aadhar is not linked to my number',
        'Option Selected': 'My Aadhar is not linked to my number',
        'Element Type': 'Form',
        Mandatory: 'No',
      },
    });
  };

  const getAadharFieldErrorMsg = () => {
    switch (error) {
      case 'MOBILE_NOT_LINKED':
        return 'Aadhaar is not linked to any mobile number';
      case 'INVALID_AADHAAR_NUMBER':
        return 'Aadhaar number is invalid';
      case 'empty_aadhar_value':
        return 'Please enter your Aadhaar Number';
      case 'invalid_aadhar_length':
        return 'Aadhaar number should be of 12 digits';
      default:
        return '';
    }
  };

  const getCaptchaFieldMsg = () => {
    switch (error) {
      case 'empty_captcha_value':
        return 'Please enter the code';
      case 'INVALID_CAPTCHA':
        return "Code didn't match, please enter the new code";
      default:
        return '';
    }
  };

  const getApiErrorMsg = () => {
    switch (error) {
      case 'OTP_LIMIT_EXCEEDED':
        return 'You have exceeded the maximum attempts to submit OTP. Please try again';
      case 'invalid_argument':
      case 'INVALID_SESSION_ID':
      case 'INPUT_DATA_ISSUE':
      case error.includes('Internal Server Error'):
        return 'Something went wrong . Please try again';
      default:
        return '';
    }
  };

  const generateCaptcha = () => {
    setCaptchaImg('');
    setIsStartAgain(false);
    return merchantFetch({
      url: 'bvs/dashboard/twirp/platform.bvs.probe.v1.ProbeAPI/AadhaarGetCaptcha',
      method: 'POST',
      data: JSON.stringify({}),
    })
      .then((res) => {
        if (res.success && res.data.captcha_image) {
          setCaptchaImg(res.data.captcha_image);
        }
        if (res.data.error_code) {
          setError(res.data.error_code);
          if (
            res.data.error_code === 'NO_PROVIDER_ERROR' ||
            res.data.error_code === 'INTERNAL_SERVER_ERROR'
          ) {
            handleDownTimeError();
          }
        }
        if (res.data.code) {
          setError(res.data.code);
          if (res.data.code === 'unavailable') {
            handleDownTimeError();
          }
          trackEvent(
            window.rzpQ.onbr().initiated('kyc.e-aadhar_get_captcha', {
              error_code: res.data.code,
              trigger: true,
            }),
          );
        } else {
          trackEvent(
            window.rzpQ.onbr().initiated('kyc.e-aadhar_get_captcha', {
              trigger: true,
              error_code: res.data?.error_code ? res.data?.error_code : null,
            }),
          );
        }
        analyticsTrack({
          objectName: 'kyc.e-aadhar get captcha',
          actionName: 'generate captcha',
          screen: 'Verify with OTP on Activation page',
          ...analyticsProperties,
        });
      })
      .catch((err) => {
        if (!err.success) {
          const apiError = err.errors[0];
          setError(apiError);
          if (apiError.includes('Internal Server Error')) {
            handleDownTimeError();
          }
        }
        trackEvent(
          window.rzpQ.onbr().initiated('kyc.e-aadhar_get_captcha', {
            trigger: true,
            error_code: err.errors[0],
          }),
        );
        analyticsTrack({
          objectName: 'kyc.e-aadhar get captcha',
          actionName: 'generate captcha Error',
          screen: 'Verify with OTP on Activation page',
          ...analyticsProperties,
        });
      });
  };

  const generateOTP = (btnStyle) => {
    const body = {
      aadhaar_number: aadharNumber,
      captcha,
    };
    return merchantFetch({
      url: 'bvs/dashboard/twirp/platform.bvs.probe.v1.ProbeAPI/AadhaarVerifyCaptchaAndSendOtp',
      method: 'POST',
      data: body,
    })
      .then((res) => {
        btnStyle.style.pointerEvents = 'initial';
        if (res.success && res.data.is_success) {
          setScreen('VerifyOTP');
          trackEvent(
            window.rzpQ.onbr().initiated('kyc.e-aadhar_send_otp', {
              trigger: true,
            }),
          );
        }
        if (res.data.error_code) {
          const errorCode = res.data.error_code;
          setError(errorCode);
          if (errorCode === 'INVALID_AADHAAR_NUMBER' || errorCode === 'INVALID_CAPTCHA') {
            setCaptchaValue('');
          }
          if (errorCode === 'NO_PROVIDER_ERROR' || errorCode === 'INTERNAL_SERVER_ERROR') {
            handleDownTimeError();
          }
        }
        if (res.data.code) {
          setError(res.data.code);
          if (res.data.code === 'invalid_argument') {
            setCaptchaValue('');
          } else if (res.data.code === 'unavailable') {
            handleDownTimeError();
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
        analyticsTrack({
          objectName: 'kyc.e-aadhar send otp',
          actionName: 'send OTP',
          screen: 'Send OTP button on Activation page',
          ...analyticsProperties,
        });
      })
      .catch((err) => {
        btnStyle.style.pointerEvents = 'initial';
        if (!err.success) {
          const apiError = err.errors[0];
          setError(apiError);
          if (apiError === 'INVALID_SESSION_ID') {
            setCaptchaValue('');
          }
          if (apiError.includes('Internal Server Error')) {
            handleDownTimeError();
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

  useEffect(() => {
    if (activeTab === 4 || isStartAgain) {
      generateCaptcha();
    }
  }, [activeTab, isStartAgain]);

  useEffect(() => {
    if (error) {
      const errorMsg = getAadharFieldErrorMsg() || getApiErrorMsg() || getCaptchaFieldMsg();
      analyticsTrack({
        objectName: 'Form Field',
        actionName: 'Validation Failed',
        screen: 'home page',
        eventAction: 'Error',
        properties: {
          error: errorMsg,
          fieldLabel: 'Aadhar Verification',
          tab: 'Documents Verification',
        },
      });
    }
  }, [error]);

  return (
    <>
      <Input
        name="aadhar_number"
        type="text"
        value={aadharNumber}
        class="Input--small Input--vTop is-mature"
        label={() => (
          <>
            Aadhaar Verification <br /> ( via OTP )
          </>
        )}
        placeholder="Enter 12 digit Aadhaar Number"
        onChange={onChange}
        disabled={!hasMobileLinked}
        propagatedError={getAadharFieldErrorMsg()}
        onFocus={() => {
          trackEvent(
            window.rzpQ.onbr().initiated('kyc.e-aadhar', {
              interaction_aadhar: true,
            }),
          );
          analyticsTrack({
            objectName: 'kyc.e-aadhar',
            actionName: 'focus',
            screen: 'Activation page',
            ...analyticsProperties,
          });
          trackEvents({
            objectName: 'Form Details',
            actionName: 'Filled',
            screen: 'home page',
            properties: {
              'Tab Title': 'Document Verification',
              'Element Type': 'Form',
              'Field Type': 'Text',
              'Field Name': 'Aadhar Verification',
              'Card Title': 'none',
            },
          });
        }}
      />

      <div className="captcha-screen">
        <div className={classList('Input-content', !hasMobileLinked ? 'Input--disabled' : '')}>
          {!!captchaImg ? (
            <>
              <img
                src={`data:image/jpeg;base64,${captchaImg}`}
                alt="E-Aadhar captcha"
                className="captcha-screen__captcha-img"
              />
              <img
                src="/dist/css/assets/onboarding/resend.svg"
                className={classList(
                  'captcha-screen__resend',
                  !hasMobileLinked ? 'captcha-screen__resend-disabled' : '',
                )}
                onClick={generateCaptcha}
              />
            </>
          ) : (
            <>
              <span className="spin-btn white medium visible" style={{ margin: '0 53px 13px' }} />
              <img src="/dist/css/assets/onboarding/disable-resend.svg" className="reload-icon" />
            </>
          )}
        </div>
        <Input
          name="captcha"
          type="text"
          value={captcha}
          class="Input--small Input--vTop is-mature"
          placeholder="Enter the captcha shown above"
          onChange={onChange}
          propagatedError={getCaptchaFieldMsg()}
          disabled={!hasMobileLinked}
          onFocus={() => {
            trackEvent(window.rzpQ.onbr().initiated('kyc.e-aadhar_code'));
            analyticsTrack({
              objectName: 'kyc.e-aadhar code',
              actionName: 'focus on enter captcha',
              screen: 'Entering captch on Activation page',
              ...analyticsProperties,
            });
            trackEvents({
              objectName: 'Form Details',
              actionName: 'Filled',
              screen: 'home page',
              properties: {
                'Tab Title': 'Document Verification',
                'Element Type': 'Form',
                'Field Type': 'Text',
                'Field Name': 'Enter the captcha shown above',
                'Card Title': 'none',
              },
            });
          }}
        />

        <div className="Input-content" style={{ marginTop: '10px' }}>
          <div className="captcha-screen__btn-container">
            <AsyncBtn.Secondary
              type="button"
              className={hasMobileLinked ? 'e-aadhar__btn' : ''}
              children="Submit & Get OTP >"
              onClick={(e) => {
                if (!aadharNumber) {
                  return setError('empty_aadhar_value');
                } else if (!captcha) {
                  return setError('empty_captcha_value');
                } else if (aadharNumber && aadharNumber.length !== 12) {
                  return setError('invalid_aadhar_length');
                }
                e.target.style.pointerEvents = 'none';
                return generateOTP(e.target);
              }}
              disabled={!hasMobileLinked}
              style={{ boxShadow: 'none' }}
              pendingState="Sending OTP"
            />
          </div>
          <div className="e-aadhar__seprator" />
        </div>
      </div>

      <div className="Input-content">
        {!!getApiErrorMsg() ? (
          <div className="e-aadhar__error">{getApiErrorMsg()}</div>
        ) : (
          <Description text="OTP will be sent to the number linked to your Aadhaar. Enter it on the next step to verify." />
        )}
      </div>

      {!isAadharEkycMandatory && (
        <div style={{ marginTop: '10px' }}>
          <Input.Check
            onChange={handleMoblieLinkedOnChange}
            defaultValue={hasMobileLinked ? '0' : '1'}
            fieldLabel="My Aadhaar is not linked to my number"
            className="e-aadhar__not-linked-checkbox"
          />
          {!hasMobileLinked && (
            <Description
              className="Input-content e-aadhar__not-linked-text"
              text="You can continue without Aadhaar verification via OTP but KYC verification and account activation will get delayed by 2 weeks. Usually it takes 3-4 days."
            />
          )}
        </div>
      )}
    </>
  );
};

export default compose(connect(null, { ...EventActions }))(GetOtpScreen);
