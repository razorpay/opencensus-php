import React, { useState, useEffect } from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';
import Input, { Description } from 'common/new-ui/Input';
import { AsyncBtn } from 'common/new-ui/Button';
import { merchantFetch } from 'merchant/utils/ajax';
import OtpInputComponent from 'common/new-ui/Input/OtpInput';
import { analyticsTrack } from 'common/utils/analytics';
import * as EventActions from 'merchant/reducers/trackEvents';

const VerifyOtp = ({
  analyticsProperties,
  mobileLinkedOnChange,
  trackEvent,
  aadharNumber,
  setCaptchaValue,
  setScreen,
  captcha,
  setError,
  setIsStartAgain,
  trackEvents,
  handleDownTimeError,
  isDigilockerEkyc,
  requestId,
}) => {
  const [otp, setOtp] = useState('');
  const [wrongOtp, setWrongOtp] = useState(false);
  const [isApiCalling, setIsApiCall] = useState(false);

  const updateOtpValue = (otpValue) => {
    setOtp(otpValue);
    setError('');
    setWrongOtp(false);
    trackEvent(window.rzpQ.onbr().initiated('kyc.e-aadhar_OTP'));
    analyticsTrack({
      objectName: 'kyc.e-aadhar OTP',
      actionName: 'Type',
      screen: 'Type OTP on Activation page',
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
        'Field Name': 'Aadhar OTP Verification',
        'Card Title': 'none',
        aadhaar_ekyc_mode: isDigilockerEkyc ? 'Digilocker native' : 'UIDAI Native',
      },
    });
  };

  const startAgainEsignVerification = () => {
    setIsStartAgain(true);
    setCaptchaValue('');
    setScreen('');
    trackEvent(window.rzpQ.onbr().initiated('kyc.e-aadhar_restart'));
    analyticsTrack({
      objectName: 'kyc.e-aadhar restart',
      actionName: 'esign steps',
      screen: 'Submit OTP on Activation page',
      ...analyticsProperties,
    });
  };

  const verifyOTP = () => {
    trackEvents({
      objectName: 'Modal CTA',
      actionName: 'Clicked',
      screen: 'home page',
      properties: {
        'CTA Label': 'Submit & Verify',
        'Modal Label': 'KYC Form',
        aadhaar_ekyc_mode: isDigilockerEkyc ? 'Digilocker native' : 'UIDAI Native',
      },
    });

    const randomPin = Math.floor(1000 + Math.random() * 9000).toString();

    const body = isDigilockerEkyc
      ? {
          aadhaar_number: aadharNumber,
          otp,
          request_id: requestId,
        }
      : {
          otp,
          captcha,
          file_password: randomPin,
        };

    const artefactcuratorAPI =
      'bvs/dashboard/twirp/platform.bvs.artefactcurator.verify.v1.DigilockerAPI/VerifyOtp';
    const probeApi = 'bvs/dashboard/twirp/platform.bvs.probe.v1.ProbeAPI/AadhaarSubmitOtp';

    const url = isDigilockerEkyc ? artefactcuratorAPI : probeApi;

    return merchantFetch({
      url,
      method: 'POST',
      data: body,
    })
      .then((res) => {
        if (res?.data?.meta?.internal_error_code === 'invalid_input_to_karza') {
          setWrongOtp(true);
          setError('invalid_input_to_karza');
          setIsApiCall(false);
          trackEvent(
            window.rzpQ.onbr().initiated('kyc.e-aadhar_OTP_submit', {
              error_code: 'invalid otp',
              trigger: true,
            }),
          );
          analyticsTrack({
            objectName: 'kyc.e-aadhar OTP submit',
            actionName: 'OTP verify failed',
            screen: 'Submit OTP on Activation page',
            ...analyticsProperties,
          });
          return;
        }

        if (res?.data?.code === 'resource_exhausted') {
          setError('failed');
          setOtp('');
          setScreen('');
          setIsApiCall(false);
          return;
        }

        if (
          isDigilockerEkyc &&
          res?.success &&
          res.data.fetchAadhaarXml !== 'failed' &&
          res?.data?.code === 'resource_exhausted'
        ) {
          mobileLinkedOnChange(true);
          setScreen('Success');
        }

        if (res.success && res?.data.is_success) {
          mobileLinkedOnChange(true);
          setScreen('Success');
        }

        if (res.success && res.data.is_valid) {
          mobileLinkedOnChange(true);
          setScreen('Success');
        }

        if (res.data.fetchAadhaarXml === 'failed') {
          handleDownTimeError(res.data);
          return;
        }

        if (res.data.error_code) {
          const errorCode = res.data.error_code;
          setError(errorCode);
          setIsApiCall(false);

          if (errorCode === 'INCORRECT_OTP') {
            setWrongOtp(true);
            setOtp('');
          } else if (errorCode === 'OTP_LIMIT_EXCEEDED' || errorCode === 'INPUT_DATA_ISSUE') {
            setCaptchaValue('');
            setScreen('');
          } else if (errorCode === 'NO_PROVIDER_ERROR' || errorCode === 'INTERNAL_SERVER_ERROR') {
            handleDownTimeError(res.data);
          }
        }
        if (res.data.code) {
          setError(res.data.code);
          setIsApiCall(false);
          if (res.data.code === 'invalid_argument') {
            setScreen('');
            setCaptchaValue('');
          } else if (res.data.code === 'unavailable') {
            handleDownTimeError(res.data);
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

        const errorCode = res.data?.error_code || res?.data?.code;

        analyticsTrack({
          objectName: 'kyc.e-aadhar OTP submit',
          actionName: 'OTP verified successfully',
          screen: 'Submit OTP on Activation page',
          properties: {
            ...analyticsProperties.properties,
            error_code: errorCode ? errorCode : null,
            status: errorCode ? 'Failure' : 'Success',
          },
        });
      })
      .catch((err) => {
        if (!err.success) {
          const apiError = err.errors[0] || '';
          setError(apiError);
          setIsApiCall(false);
          if (apiError === 'INVALID_SESSION_ID') {
            setScreen('');
          } else if (apiError.includes('Internal Server Error')) {
            handleDownTimeError(err);
          }
        }
        trackEvent(
          window.rzpQ.onbr().initiated('kyc.e-aadhar_OTP_submit', {
            error_code: err.errors[0],
            trigger: true,
          }),
        );
        analyticsTrack({
          objectName: 'kyc.e-aadhar OTP submit',
          actionName: 'OTP verify failed',
          screen: 'Submit OTP on Activation page',
          ...analyticsProperties,
        });
      });
  };

  useEffect(() => {
    if (otp.length === 6) {
      verifyOTP();
      setIsApiCall(true);
    }
  }, [otp]);

  useEffect(() => {
    if (wrongOtp) {
      analyticsTrack({
        objectName: 'Form Field',
        actionName: 'Validation Failed',
        screen: 'home page',
        eventAction: 'Error',
        properties: {
          error: 'Invalid OTP. Try again',
          fieldLabel: 'Aadhar Verification',
          tab: 'Documents Verification',
          aadhaar_ekyc_mode: isDigilockerEkyc ? 'Digilocker native' : 'UIDAInative',
        },
      });
    }
  }, [wrongOtp, isDigilockerEkyc]);

  return (
    <>
      <div class="Input-label otp-label" style={{ textAlign: 'left' }}>
        Aadhaar Verification <br /> ( via OTP )
      </div>
      <div className="disabled-aadhaar">
        <Input
          type="text"
          className="Input--small Input--vTop is-mature"
          disabled={true}
          defaultValue={aadharNumber}
        />
      </div>

      <div className="otp-screen">
        <div className="Input-content otp-screen__otp">
          <OtpInputComponent
            heading="OTP has been sent to the number linked with Aadhaar"
            onComplete={updateOtpValue}
            onChange={updateOtpValue}
            wrong={wrongOtp}
          />
          {wrongOtp && <div className="e-aadhar__error">Invalid OTP. Try again</div>}
          <p className="m-t m-b resend-otp">
            Didn’t receive an OTP?{' '}
            <AsyncBtn.Transparent
              pendingState="Sending OTP..."
              onClick={startAgainEsignVerification}
              showLoader={false}
            >
              Start again
            </AsyncBtn.Transparent>
          </p>
        </div>

        <div className="Input-content">
          <AsyncBtn.Secondary
            type="button"
            className={otp.length === 6 ? 'e-aadhar__btn' : ''}
            children="Submit & Verify >"
            isPending={isApiCalling}
            disabled={otp.length !== 6}
            style={{ boxShadow: 'none' }}
            pendingState="Verifying"
          />
          <div className="e-aadhar__seprator" />
          <Description
            className="e-aadhar__desc e-aadhar__consent"
            text={
              <>
                By verifying, you consent to share your Aadhaar details with Razorpay for KYC and
                you agree with the{' '}
                <a
                  href="https://razorpay.com/privacy/"
                  target="_blank"
                  rel="noreferrer noopener"
                  onClick={() => {
                    trackEvent(window.rzpQ.onbr().initiated('kyc.e-aadhar_consent_link'));
                    analyticsTrack({
                      objectName: 'kyc.e-aadhar consent link',
                      actionName: 'click on privacy policy (e-aadhar)',
                      screen: 'KYC on Activation page',
                      ...analyticsProperties,
                    });
                  }}
                >
                  privacy policy
                </a>
              </>
            }
          />
        </div>
      </div>
    </>
  );
};

export default compose(connect(null, { ...EventActions }))(VerifyOtp);
