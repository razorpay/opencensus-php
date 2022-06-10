import React, { useEffect, useState } from 'react';
import * as Yup from 'yup';
import { Formik, Form } from 'formik';
import View from '@razorpay/blade-old/src/atoms/View';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import TextInput from '@razorpay/blade-old/src/atoms/TextInput';
import CheckBox from '@razorpay/blade-old/src/atoms/Checkbox';
import Button from '@razorpay/blade-old/src/atoms/Button';
import { useMutation } from 'react-query';
import { fetch } from 'common/services/rest/rest-fetch';
import { analyticsTrack } from 'common/services/tracking/segment';
import { useApp } from 'common/context/App';
import { Divider, StyledView } from './Styled';
import useActivation from '../hooks/useActivation';
import ResendIcon from './ResendIcon.svg';
import { isUnregisteredBusiness } from '../services/utils';

const generateCaptcha = async () => {
  const fetchData = await fetch<any>({
    url: 'bvs/dashboard/twirp/platform.bvs.probe.v1.ProbeAPI/AadhaarGetCaptcha',
    method: 'POST',
    data: JSON.stringify({}),
  });
  return fetchData;
};

interface GetOTPPropsT {
  goToNextScreen: ({ nextScreen: string }) => void;
  setAadharNumber: (data: string) => void;
  setRequestId: (data: string) => void;
  setOTP: (data: string) => void;
  setUserEnteredCaptcha: (data: string) => void;
  // eslint-disable-next-line react/no-unused-prop-types
  otp: string;
  aadharError: string;
  disabled: boolean;
  handleDownTimeError: (data) => void;
}

const GetOTP: React.FC<GetOTPPropsT> = ({
  goToNextScreen,
  setOTP,
  setUserEnteredCaptcha,
  setAadharNumber,
  setRequestId,
  aadharError,
  disabled,
  handleDownTimeError,
}) => {
  const { postData, data } = useActivation();
  const [isAadharLinkedToMobile, setIsaadharLinkedToMobile] = useState(
    data && data.stakeholder ? !!data.stakeholder.aadhaar_linked : true,
  );
  const [captcha, setCaptcha] = useState('');
  const [hasCaptchaVerified, setIsCaptchaVerified] = useState(false);
  const [otpContext, setotpContext] = useState<any>({});
  const [apiError, setApiError] = useState('');
  const [fetchCaptcha] = useMutation(generateCaptcha, {
    onSuccess: (response) => {
      setCaptcha(response.captcha_image);
      if (
        response?.error_code === 'NO_PROVIDER_ERROR' ||
        response?.error_code === 'INTERNAL_SERVER_ERROR' ||
        response?.code === 'unavailable'
      ) {
        handleDownTimeError(response);
      }
    },
    onError: (err: { response: { errors: Array<string> } }) => {
      if (err.response.errors[0].includes('Internal Server Error')) {
        handleDownTimeError(err.response);
      }
    },
  });
  const { user, experiments } = useApp();
  const isDigilockerEkyc = experiments.isDigilockerEkyc;
  const shouldHideAadharUploadCheckbox =
    experiments.isAadharEkycMandatory && isUnregisteredBusiness(data?.business_type);

  const getOTPAPi = async (data) => {
    const artefactcuratorAPI =
      'bvs/dashboard/twirp/platform.bvs.artefactcurator.verify.v1.DigilockerAPI/SendOtp';
    const probeApi =
      'bvs/dashboard/twirp/platform.bvs.probe.v1.ProbeAPI/AadhaarVerifyCaptchaAndSendOtp';

    const url = isDigilockerEkyc ? artefactcuratorAPI : probeApi;

    const fetchData = await fetch<any>({
      url,
      method: 'POST',
      data,
    });
    if (isDigilockerEkyc) {
      setRequestId(fetchData?.request_id);
    }
    return fetchData;
  };

  const [fetchOTP] = useMutation(getOTPAPi, {
    onSuccess: (response) => {
      if (response?.msg === 'invalid input details') {
        setApiError('INVALID_AADHAAR_NUMBER');
      }
      if (response?.code === 'internal') {
        setApiError('internal');
      }
      if (response.is_success) {
        setIsCaptchaVerified(response.is_success);
        analyticsTrack({
          objectName: 'SignUp',
          actionName: 'fetch otp',
          screen: 'home page',
          eventAction: 'success',
          user,
          properties: {
            aadhaar_ekyc_mode: experiments.isDigilockerEkyc ? 'Digilocker native' : 'UIDAI native',
          },
        });
      } else if (response.error_code) {
        analyticsTrack({
          objectName: 'SignUp',
          actionName: 'fetch otp',
          screen: 'home page',
          eventAction: 'failure',
          user,
          properties: {
            aadhaar_ekyc_mode: experiments.isDigilockerEkyc ? 'Digilocker native' : 'UIDAI native',
          },
        });
        setApiError(response.error_code);
        if (
          response.error_code === 'NO_PROVIDER_ERROR' ||
          response.error_code === 'INTERNAL_SERVER_ERROR'
        ) {
          handleDownTimeError(response);
        }
      } else if (response?.code === 'unavailable') {
        handleDownTimeError(response);
      }
    },
    onError: (err: { response: { errors: Array<string> } }) => {
      if (err.response.errors[0].includes('Internal Server Error')) {
        handleDownTimeError(err.response);
      }
    },
  });

  const mobileNotLinked = (status) => {
    setIsaadharLinkedToMobile(!status);
    postData({ stakeholder: { aadhaar_linked: status ? 0 : 1 } });
    analyticsTrack({
      objectName: 'SignUp',
      actionName: `${
        isAadharLinkedToMobile ? 'Adhar not linked checkedBox' : 'Adhar linked checkedBox'
      } initiated`,
      screen: 'home page',
      eventAction: 'initiated',
      user,
      properties: {
        aadhaar_ekyc_mode: experiments.isDigilockerEkyc ? 'Digilocker native' : 'UIDAI native',
      },
    });
  };

  const handleSubmit = async (payload) => {
    const otpData = {
      aadhaar_number: payload.aadharNumber,
      ...(isDigilockerEkyc ? {} : { captcha: payload.captchaCode }),
    };

    setotpContext(payload);
    setAadharNumber(payload.aadharNumber);
    if (!isDigilockerEkyc) {
      setUserEnteredCaptcha(payload.captchaCode);
    }
    await fetchOTP(otpData);
  };

  useEffect(() => {
    if (!isDigilockerEkyc) {
      fetchCaptcha();
    }
  }, []);

  useEffect(() => {
    if (hasCaptchaVerified) {
      setOTP(otpContext.captchaCode);
      const nextScreen = 'VerifyOTP';
      goToNextScreen({ nextScreen });
    }
  }, [hasCaptchaVerified]);

  return (
    <Formik
      initialValues={{ aadharNumber: '', ...(isDigilockerEkyc ? {} : { captchaCode: '' }) }}
      validationSchema={() => {
        return Yup.object().shape({
          aadharNumber: Yup.string()
            .length(12, 'Aadhaar should be of 12 digits')
            .required('Aadhaar Number is a required field'),
          captchaCode: Yup.lazy(() => {
            if (isDigilockerEkyc) {
              return Yup.string().nullable();
            } else {
              return Yup.string().required('captcha is a required field');
            }
          }),
        });
      }}
      onSubmit={handleSubmit}
    >
      {(formikProps) => (
        <Form>
          <Space margin={[0, 0, 5, 0]}>
            <View>
              <Text size="medium" weight="bold" color="shade.970">
                Aadhaar Verification ( via OTP )
              </Text>

              {aadharError === 'OTP_LIMIT_EXCEEDED' && (
                <Text size="xsmall" color="negative.900">
                  You have exceeded the maximum attempts to submit OTP. Please try again
                </Text>
              )}

              {(aadharError === 'INPUT_DATA_ISSUE' ||
                apiError === 'NO_PROVIDER_ERROR' ||
                apiError === 'INVALID_SESSION_ID' ||
                apiError === 'invalid_argument') && (
                <Text size="xsmall" color="negative.900">
                  Something went wrong. Please try again
                </Text>
              )}

              <StyledView disable={!isAadharLinkedToMobile || disabled}>
                <Space margin={[4, 0, 0, 0]}>
                  <View>
                    <TextInput
                      width="auto"
                      name="aadharNumber"
                      type="text"
                      label="12 Digit Aadhaar Number"
                      value={formikProps.values.aadharNumber}
                      errorText={
                        apiError === 'INVALID_AADHAAR_NUMBER'
                          ? 'Aadhaar number is invalid'
                          : apiError === 'MOBILE_NOT_LINKED'
                          ? 'This Aadhaar is not linked to any number'
                          : apiError === 'internal'
                          ? 'Something went wrong. Please try again'
                          : formikProps.touched.aadharNumber && formikProps.errors.aadharNumber
                      }
                      onChange={(value) => {
                        formikProps.setFieldValue('aadharNumber', value.trim());
                        if (aadharError) {
                          setApiError('');
                        }
                      }}
                      onBlur={(value) => {
                        formikProps.setFieldTouched('aadharNumber', value.trim());
                        analyticsTrack({
                          objectName: 'SignUp',
                          actionName: 'Aadhar number in get otp',
                          screen: 'home page',
                          eventAction: 'initiated',
                          user,
                          properties: {
                            aadhaar_ekyc_mode: experiments.isDigilockerEkyc
                              ? 'Digilocker native'
                              : 'UIDAI native',
                          },
                        });
                      }}
                    />
                    {!isDigilockerEkyc && (
                      <Space margin={[4, 0, 4, 0]}>
                        <View>
                          {captcha ? (
                            <>
                              <img
                                src={`data:image/jpeg;base64,${captcha}`}
                                alt="E-Aadhar captcha"
                              />
                              <img
                                src={ResendIcon}
                                className="captcha-screen__resend"
                                onClick={() => fetchCaptcha()}
                              />
                            </>
                          ) : (
                            <Text>Loading...</Text>
                          )}
                        </View>
                      </Space>
                    )}
                  </View>
                </Space>
                {!isDigilockerEkyc && (
                  <Space margin={[4, 0, 0, 0]}>
                    <View>
                      <TextInput
                        width="auto"
                        name="captchaCode"
                        type="text"
                        label="Enter the captcha shown above"
                        value={formikProps.values.captchaCode}
                        errorText={
                          apiError === 'INVALID_CAPTCHA'
                            ? 'Code didn’t match, please enter the new code'
                            : formikProps.touched.captchaCode && formikProps.errors.captchaCode
                        }
                        onChange={(value) => {
                          formikProps.setFieldValue('captchaCode', value.trim());
                          if (apiError) {
                            setApiError('');
                          }
                        }}
                        onBlur={(value) => {
                          formikProps.setFieldTouched('captchaCode', value.trim());
                          analyticsTrack({
                            objectName: 'SignUp',
                            actionName: 'captcha code',
                            screen: 'home page',
                            eventAction: 'initiated',
                            user,
                            properties: {
                              aadhaar_ekyc_mode: experiments.isDigilockerEkyc
                                ? 'Digilocker native'
                                : 'UIDAI native',
                            },
                          });
                        }}
                      />
                    </View>
                  </Space>
                )}
                <Flex alignItems="center">
                  <StyledView>
                    <Space padding={[1.5, 0]}>
                      <View>
                        {formikProps.isSubmitting ? (
                          <Button variant="secondary" size="small" type="submit">
                            Submitting ...
                          </Button>
                        ) : (
                          <Button
                            variant="secondary"
                            size="small"
                            type="submit"
                            icon="chevronRight"
                            iconAlign="right"
                            disabled={!isAadharLinkedToMobile || disabled}
                          >
                            Submit &amp; Get OTP
                          </Button>
                        )}
                      </View>
                    </Space>
                  </StyledView>
                </Flex>
              </StyledView>
              <Space margin={[1, 0, 1]}>
                <Divider />
              </Space>

              <Space padding={[0, 0, 1.5]}>
                <View>
                  <StyledView disable={!isAadharLinkedToMobile || disabled}>
                    <Text size="xsmall" color="shade.950">
                      An OTP will be sent to number linked with your Aadhaar. Enter it on next step
                      to verify
                    </Text>
                  </StyledView>

                  {!shouldHideAadharUploadCheckbox && (
                    <Space margin={[2, 0, 1]}>
                      <View>
                        <CheckBox
                          onChange={mobileNotLinked}
                          title="My Aadhaar is not linked with any mobile number"
                          checked={!isAadharLinkedToMobile}
                          disabled={disabled}
                        />
                      </View>
                    </Space>
                  )}

                  {!isAadharLinkedToMobile && (
                    <Text size="small" color="mustard.900">
                      You can continue without Aadhaar verification via OTP but KYC verification and
                      account activation will get delayed by 2 weeks. Usually it takes 3-4 days.
                    </Text>
                  )}
                </View>
              </Space>
            </View>
          </Space>
        </Form>
      )}
    </Formik>
  );
};

export default GetOTP;
