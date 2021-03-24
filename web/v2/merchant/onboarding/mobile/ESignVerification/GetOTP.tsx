import React, { useEffect, useState } from 'react';
import * as Yup from 'yup';
import { Formik, Form } from 'formik';
import View from '@razorpay/blade/src/atoms/View';
import Space from '@razorpay/blade/src/atoms/Space';
import Text from '@razorpay/blade/src/atoms/Text';
import TextInput from '@razorpay/blade/src/atoms/TextInput';
import Flex from '@razorpay/blade/src/atoms/Flex';
import Button from '@razorpay/blade/src/atoms/Button';
import { useMutation } from 'react-query';
import { fetch } from 'v2/services/rest/rest-fetch';
import { analyticsTrack, getCommonSegmentProperties } from '../../../../services/tracking/segment';
import { useApp } from 'v2/context/App';

const generateCaptcha = async () => {
  const fetchData = await fetch<any>({
    url: 'bvs/dashboard/twirp/platform.bvs.probe.v1.ProbeAPI/AadhaarGetCaptcha',
    method: 'POST',
    data: JSON.stringify({}),
  });
  return fetchData;
};

const getOTPAPi = async (data) => {
  const fetchData = await fetch<any>({
    url: 'bvs/dashboard/twirp/platform.bvs.probe.v1.ProbeAPI/AadhaarVerifyCaptchaAndSendOtp',
    method: 'POST',
    data,
  });
  return fetchData;
};

interface GetOTPPropsT {
  goToNextScreen: ({ nextScreen: string }) => void;
  setAadharNumber: (data: string) => void;
  setPin: (data: string) => void;
  setOTP: (data: string) => void;
  setUserEnteredCaptcha: (data: string) => void;
  setAadharInputError: (data: string) => void;
  aadharNumber: string;
  otp: string;
  aadharError: string;
}

const GetOTP: React.FC<GetOTPPropsT> = ({
  goToNextScreen,
  aadharNumber,
  setPin,
  setOTP,
  setUserEnteredCaptcha,
  setAadharInputError,
  setAadharNumber,
  aadharError,
}) => {
  const [captcha, setIsCaptcha] = useState('');
  const [hasCaptchaVerified, setIsCaptchaVerified] = useState(false);
  const [otpContext, setotpContext] = useState<any>({});
  const [apiError, setApiError] = useState('');
  const [fetchCaptcha] = useMutation(generateCaptcha, {
    onSuccess: (response) => {
      setIsCaptcha(response.captcha_image);
    },
  });
  const { user } = useApp();

  const [fetchOTP] = useMutation(getOTPAPi, {
    onSuccess: (response) => {
      if (response.is_success) {
        setIsCaptchaVerified(response.is_success);
        analyticsTrack({
          objectName: 'SignUp',
          actionName: 'fetch otp success',
          screen: 'home page',
          properties: {
            userId: user.id,
            ...getCommonSegmentProperties(),
          },
        });
      } else if (response.error_code) {
        analyticsTrack({
          objectName: 'SignUp',
          actionName: 'fetch otp failure',
          screen: 'home page',
          properties: {
            userId: user.id,
            ...getCommonSegmentProperties(),
          },
        });
        if (response.error_code === 'MOBILE_NOT_LINKED') {
          const nextScreen = 'AadharInput';
          goToNextScreen({ nextScreen });
          setAadharInputError('MOBILE_NOT_LINKED');
        }
        setApiError(response.error_code);
      }
    },
  });

  const handleSubmit = async (payload) => {
    const data = {
      aadhaar_number: payload.aadharNumber,
      captcha: payload.captchaCode,
    };
    setotpContext(payload);
    setAadharNumber(payload.aadharNumber);
    setUserEnteredCaptcha(payload.captchaCode);
    await fetchOTP(data);
  };

  useEffect(() => {
    fetchCaptcha();
  }, []);

  useEffect(() => {
    if (hasCaptchaVerified) {
      setOTP(otpContext.captchaCode);
      setPin(otpContext.createdPin);
      const nextScreen = 'VerifyOTP';
      goToNextScreen({ nextScreen });
    }
  }, [hasCaptchaVerified]);

  return (
    <Formik
      initialValues={{
        captchaCode: '',
        createdPin: '',
        aadharNumber,
      }}
      validationSchema={() => {
        return Yup.object().shape({
          aadharNumber: Yup.string()
            .length(12, 'Aadhar should be of 12 digits')
            .required('Aadhar Number is a required field'),
          captchaCode: Yup.string().required('captcha is a required field'),
          createdPin: Yup.string()
            .length(4, 'Pin number should be of 4 digits')
            .required('Pin is a required field'),
        });
      }}
      onSubmit={handleSubmit}
    >
      {(formikProps) => (
        <Form>
          <Space margin={[0, 0, 5, 0]}>
            <View>
              <Text size="medium" weight="bold" color="shade.970">
                Aadhar Verification
              </Text>
              {!aadharError && (
                <Text size="xsmall" color="shade.950">
                  An OTP will be sent to number linked with your Aadhar
                </Text>
              )}

              {aadharError === 'OTP_LIMIT_EXCEEDED' && (
                <Text size="xsmall" color="negative.900">
                  You have exceeded the maximum attempts to submit OTP. Please try again
                </Text>
              )}

              {aadharError === 'INPUT_DATA_ISSUE' && (
                <Text size="xsmall" color="negative.900">
                  Something went wrong. Please try again
                </Text>
              )}

              <Space margin={[4, 0, 0, 0]}>
                <View>
                  <TextInput
                    width="auto"
                    name="aadharNumber"
                    type="text"
                    label="12 Digit Aadhar Number"
                    value={formikProps.values.aadharNumber}
                    errorText={
                      apiError === 'INVALID_AADHAAR_NUMBER'
                        ? 'Aadhar number is invalid'
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
                        actionName: 'Aadhar number in get otp initiated',
                        screen: 'home page',
                        properties: {
                          userId: user.id,
                          ...getCommonSegmentProperties(),
                        },
                      });
                    }}
                  />
                  <Space margin={[4, 0, 4, 0]}>
                    <View>
                      {captcha ? (
                        <>
                          <img src={`data:image/jpeg;base64,${captcha}`} alt="E-Aadhar captcha" />
                          <img
                            src="/dist/css/assets/onboarding/resend.svg"
                            className="captcha-screen__resend"
                            onClick={() => fetchCaptcha()}
                          />
                        </>
                      ) : (
                        <Text>Loading...</Text>
                      )}
                    </View>
                  </Space>
                </View>
              </Space>
              <Space margin={[4, 0, 0, 0]}>
                <View>
                  <TextInput
                    width="auto"
                    name="captchaCode"
                    type="text"
                    label="Enter the code shown"
                    value={formikProps.values.captchaCode}
                    errorText={
                      apiError === 'INVALID_CAPTCHA'
                        ? 'Code didn’t match, please enter the new code'
                        : apiError === 'NO_PROVIDER_ERROR' ||
                          apiError === 'INVALID_SESSION_ID' ||
                          apiError === 'invalid_argument'
                        ? 'Something went wrong. Please try again'
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
                        actionName: 'captcha code initiated',
                        screen: 'home page',
                        properties: {
                          userId: user.id,
                          ...getCommonSegmentProperties(),
                        },
                      });
                    }}
                  />
                </View>
              </Space>
              <Space margin={[4, 0, 0, 0]}>
                <View>
                  <TextInput
                    width="auto"
                    name="createdPin"
                    type="text"
                    label="Create a PIN"
                    value={formikProps.values.createdPin}
                    errorText={formikProps.touched.createdPin && formikProps.errors.createdPin}
                    onChange={(value) => formikProps.setFieldValue('createdPin', value.trim())}
                    onBlur={(value) => {
                      formikProps.setFieldTouched('createdPin', value.trim());
                      analyticsTrack({
                        objectName: 'SignUp',
                        actionName: 'create pin initiated',
                        screen: 'home page',
                        properties: {
                          userId: user.id,
                          ...getCommonSegmentProperties(),
                        },
                      });
                    }}
                    helpText="Create a 4 digit PIN to secure your aadhar details with us"
                  />
                </View>
              </Space>
              <Flex alignItems="center">
                <Space padding={[1, 0, 0]}>
                  <View>
                    <Space padding={[0, 1, 0, 0]}>
                      <View>
                        <Button
                          variant="tertiary"
                          size="small"
                          onClick={() => {
                            const nextScreen = 'AadharInput';
                            goToNextScreen({ nextScreen });
                          }}
                        >
                          Back
                        </Button>
                      </View>
                    </Space>
                    <Space padding={[1.5, 0]}>
                      <View>
                        <Button variant="secondary" size="small" type="submit">
                          Get OTP
                        </Button>
                      </View>
                    </Space>
                  </View>
                </Space>
              </Flex>
            </View>
          </Space>
        </Form>
      )}
    </Formik>
  );
};

export default GetOTP;
