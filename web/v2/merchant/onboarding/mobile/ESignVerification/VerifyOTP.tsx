import React, { useEffect, useState } from 'react';
import * as Yup from 'yup';
import { Formik, Form } from 'formik';
import Text from '@razorpay/blade/src/atoms/Text';
import View from '@razorpay/blade/src/atoms/View';
import Space from '@razorpay/blade/src/atoms/Space';
import TextInput from '@razorpay/blade/src/atoms/TextInput';
import Button from '@razorpay/blade/src/atoms/Button';
import Flex from '@razorpay/blade/src/atoms/Flex';
import { useMutation } from 'react-query';
import { fetch } from 'v2/services/rest/rest-fetch';
import { analyticsTrack } from '../../../../services/tracking/segment';
import { useApp } from 'v2/context/App';
import { Divider } from './Styled';

interface VerifyOtpPropsT {
  goToNextScreen: ({ nextScreen: string }) => void;
  setAadharInputError: (data: string) => void;
  aadharNumber: string;
  pin: string;
  inputCaptcha: string;
}

const verifyAadhar = async (data) => {
  const fetchData = await fetch<any>({
    url: 'bvs/dashboard/twirp/platform.bvs.probe.v1.ProbeAPI/AadhaarSubmitOtp',
    method: 'POST',
    data,
  });
  return fetchData;
};

const VerifyOTP: React.FC<VerifyOtpPropsT> = ({
  goToNextScreen,
  setAadharInputError,
  aadharNumber,
  pin,
  inputCaptcha,
}) => {
  const [isOtpVerified, setOtpVerified] = useState(false);
  const [apiError, setApiError] = useState('');
  const { user } = useApp();

  const [fetchOTP] = useMutation(verifyAadhar, {
    onSuccess: (response) => {
      if (response.is_valid) {
        setOtpVerified(response.is_valid);
        analyticsTrack({
          objectName: 'SignUp',
          actionName: 'otp success',
          screen: 'home page',
          properties: {
            userId: user.id,
          },
        });
      } else if (response.error_code) {
        analyticsTrack({
          objectName: 'SignUp',
          actionName: 'otp failure',
          screen: 'home page',
          properties: {
            userId: user.id,
          },
        });
        setApiError(response.error_code);
        if (
          response.error_code === 'OTP_LIMIT_EXCEEDED' ||
          response.error_code === 'INPUT_DATA_ISSUE'
        ) {
          setAadharInputError(response.error_code);
          const nextScreen = 'GetOTP';
          goToNextScreen({ nextScreen });
        }
      }
    },
  });

  const handleSubmit = async (payload) => {
    const data = {
      otp: payload.enteredOTP,
      captcha: inputCaptcha,
      file_password: pin,
    };
    await fetchOTP(data);
  };

  useEffect(() => {
    if (isOtpVerified) {
      const nextScreen = 'AadharSuccess';
      goToNextScreen({ nextScreen });
    }
  }, [isOtpVerified]);

  return (
    <Formik
      initialValues={{
        enteredOTP: '',
        aadharNumber,
      }}
      validationSchema={() => {
        return Yup.object().shape({
          enteredOTP: Yup.string()
            .length(6, 'OTP number should be of 6 digits')
            .required('OTP Number is a required field'),
        });
      }}
      onSubmit={handleSubmit}
    >
      {(formikProps) => (
        <Form>
          <Space margin={[4, 0, 5, 0]}>
            <View>
              <Text size="medium" weight="bold" color="shade.970">
                Aadhar Verification
              </Text>
              <Text size="xsmall" color="shade.950">
                An OTP will be sent to number linked with your Aadhar
              </Text>
              <Space margin={[4, 0, 4, 0]}>
                <View>
                  <TextInput
                    width="auto"
                    name="aadharNumber"
                    type="text"
                    label="12 Digit Aadhar Number"
                    value={formikProps.values.aadharNumber}
                    errorText={formikProps.touched.aadharNumber && formikProps.errors.aadharNumber}
                    onChange={(value) => formikProps.setFieldValue('aadharNumber', value.trim())}
                    onBlur={(value) => formikProps.setFieldTouched('aadharNumber', value.trim())}
                    disabled
                  />
                </View>
              </Space>
              <TextInput
                width="auto"
                name="enteredOTP"
                type="text"
                label="Enter OTP"
                value={formikProps.values.enteredOTP}
                helpText="An OTP has been sent to mobile number linked with your Aadhar"
                errorText={
                  apiError === 'INCORRECT_OTP'
                    ? 'Invalid OTP. Try again'
                    : formikProps.touched.enteredOTP && formikProps.errors.enteredOTP
                }
                onChange={(value) => {
                  formikProps.setFieldValue('enteredOTP', value.trim());
                  if (apiError) {
                    setApiError('');
                  }
                }}
                onBlur={(value) => {
                  formikProps.setFieldTouched('enteredOTP', value.trim());
                  analyticsTrack({
                    objectName: 'SignUp',
                    actionName: 'otp initiated',
                    screen: 'home page',
                    properties: {
                      userId: user.id,
                    },
                  });
                }}
              />

              <Space margin={[4, 0, 0]}>
                <Divider />
              </Space>

              <Space padding={[1.5, 0]}>
                <View>
                  <Button variant="secondary" size="small" type="submit">
                    Submit OTP
                  </Button>
                </View>
              </Space>
              <Flex alignItems="center">
                <Space padding={[0, 0, 4]}>
                  <View>
                    <Text size="small" color="shade.960">
                      Did not recieve OTP?
                    </Text>
                    <Button
                      variant="tertiary"
                      size="small"
                      onClick={() => {
                        const nextScreen = 'AadharInput';
                        goToNextScreen({ nextScreen });
                      }}
                    >
                      Start again
                    </Button>
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

export default VerifyOTP;
