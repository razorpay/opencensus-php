import React, { useEffect, useState } from 'react';
import * as Yup from 'yup';
import { Formik, Form, useFormikContext } from 'formik';
import Text from '@razorpay/blade-old/src/atoms/Text';
import View from '@razorpay/blade-old/src/atoms/View';
import Space from '@razorpay/blade-old/src/atoms/Space';
import TextInput from '@razorpay/blade-old/src/atoms/TextInput';
import Button from '@razorpay/blade-old/src/atoms/Button';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import Link from '@razorpay/commander-shield/src/shared/Link';
import { useMutation } from 'react-query';
import { fetch } from 'common/services/rest/rest-fetch';
import useActivation from '../hooks/useActivation';
import { analyticsTrack } from 'common/services/tracking/segment';
import { useApp } from 'common/context/App';
import { Divider } from './Styled';

interface VerifyOtpPropsT {
  goToNextScreen: ({ nextScreen: string }) => void;
  setAadharInputError: (data: string) => void;
  aadharNumber: string;
  inputCaptcha: string;
  handleDownTimeError: () => void;
}

const verifyAadhar = async (data) => {
  const fetchData = await fetch<any>({
    url: 'bvs/dashboard/twirp/platform.bvs.probe.v1.ProbeAPI/AadhaarSubmitOtp',
    method: 'POST',
    data,
  });
  return fetchData;
};

const AutoSubmit: React.FC = () => {
  const { submitForm, values }: any = useFormikContext();

  useEffect(() => {
    if (values.enteredOTP.length === 6) {
      submitForm();
    }
  }, [values, submitForm]);

  return null;
};

const VerifyOTP: React.FC<VerifyOtpPropsT> = ({
  goToNextScreen,
  setAadharInputError,
  aadharNumber,
  inputCaptcha,
  handleDownTimeError,
}) => {
  const [apiError, setApiError] = useState('');
  const { user } = useApp();
  const { postData } = useActivation();

  const [fetchOTP] = useMutation(verifyAadhar, {
    onSuccess: (response) => {
      if (response.is_valid) {
        postData({ stakeholder: { aadhaar_linked: 1 } });
        const nextScreen = 'AadharSuccess';
        goToNextScreen({ nextScreen });
        analyticsTrack({
          objectName: 'SignUp',
          actionName: 'otp',
          screen: 'home page',
          eventAction: 'success',
          user,
        });
      } else if (response.error_code) {
        analyticsTrack({
          objectName: 'SignUp',
          actionName: 'otp',
          screen: 'home page',
          eventAction: 'failure',
          user,
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
        if (
          response.error_code === 'NO_PROVIDER_ERROR' ||
          response.error_code === 'INTERNAL_SERVER_ERROR'
        ) {
          handleDownTimeError();
        }
      } else if (response?.code === 'unavailable') {
        handleDownTimeError();
      }
    },
    onError: (err: { response: { errors: Array<string> } }) => {
      if (err.response.errors[0].includes('Internal Server Error')) {
        handleDownTimeError();
      }
    },
  });

  const handleSubmit = async (payload) => {
    const randomPin = Math.floor(1000 + Math.random() * 9000).toString();
    const data = {
      otp: payload.enteredOTP,
      captcha: inputCaptcha,
      file_password: randomPin,
    };
    await fetchOTP(data);
  };

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
                Aadhar Verification ( Via OTP )
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
                    actionName: 'otp',
                    screen: 'home page',
                    eventAction: 'initiated',
                    user,
                  });
                }}
                disabled={formikProps.isSubmitting}
              />

              <Space margin={[4, 0, 0]}>
                <Divider />
              </Space>

              <Space padding={[1.5, 0]}>
                <View>
                  {formikProps.isSubmitting ? (
                    <Button variant="secondary" size="small" type="submit">
                      Submitting OTP ...
                    </Button>
                  ) : (
                    <Button
                      variant="secondary"
                      size="small"
                      icon="chevronRight"
                      iconAlign="right"
                      type="submit"
                      disabled
                    >
                      Submit &amp; Verify
                    </Button>
                  )}
                </View>
              </Space>
              <Flex alignItems="center">
                <Space padding={[0, 0, 1.5]}>
                  <View>
                    <Text size="small" color="shade.960">
                      Did not receive OTP?
                    </Text>
                    <Button
                      variant="tertiary"
                      size="small"
                      onClick={() => {
                        const nextScreen = 'GetOTP';
                        goToNextScreen({ nextScreen });
                      }}
                    >
                      Start again
                    </Button>
                  </View>
                </Space>
              </Flex>
              <AutoSubmit />
              <Divider />
              <Space padding={[1.5, 0, 2]}>
                <View>
                  <Text size="xsmall" color="shade.960">
                    By verifying, you consent to share your aadhar details with us and agree to{' '}
                    <Link
                      size="xsmall"
                      href="https://razorpay.com/privacy/"
                      target="_blank"
                      onClick={() => {}}
                    >
                      privacy policy
                    </Link>{' '}
                  </Text>
                </View>
              </Space>
            </View>
          </Space>
        </Form>
      )}
    </Formik>
  );
};

export default VerifyOTP;
