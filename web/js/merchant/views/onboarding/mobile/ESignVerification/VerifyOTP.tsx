import React, { useEffect, useState } from 'react';
import { useMutation } from '@tanstack/react-query';
import * as Yup from 'yup';
import { Formik, Form, useFormikContext } from 'formik';
import Text from '@razorpay/blade-old/src/atoms/Text';
import View from '@razorpay/blade-old/src/atoms/View';
import Space from '@razorpay/blade-old/src/atoms/Space';
import TextInput from '@razorpay/blade-old/src/atoms/TextInput';
import Button from '@razorpay/blade-old/src/atoms/Button';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import Link from 'common/components/Link';
import { fetch } from '@federated/apps/shell/rest-fetch';
import useActivation from 'merchant/views/onboarding/mobile/hooks/useActivation';
import { analyticsTrack } from 'common/services/tracking/segment';
import { useApp } from 'common/context/App';
import { Divider } from './Styled';

type goToNextScreenProps = {
  nextScreen: string;
};
interface VerifyOtpPropsT {
  goToNextScreen: (args: goToNextScreenProps) => void;
  setAadharInputError: (data: string) => void;
  aadharNumber: string;
  inputCaptcha: string;
  requestId: string;
  handleDownTimeError: (data) => void;
}

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
  requestId,
  inputCaptcha,
  handleDownTimeError,
}) => {
  const [apiError, setApiError] = useState('');
  const { user, experiments } = useApp();
  const { postData } = useActivation();

  const verifyAadhar = async (data) => {
    const artefactcuratorAPI =
      'bvs/dashboard/twirp/platform.bvs.artefactcurator.verify.v1.DigilockerAPI/VerifyOtp';
    const probeApi = 'bvs/dashboard/twirp/platform.bvs.probe.v1.ProbeAPI/AadhaarSubmitOtp';

    const url = experiments.isDigilockerEkyc ? artefactcuratorAPI : probeApi;

    const fetchData = await fetch<any>({
      url,
      method: 'POST',
      data,
    });
    return fetchData;
  };

  const { mutate: fetchOTP } = useMutation({
    mutationFn: verifyAadhar,
    onSuccess: (response) => {
      if (response?.meta?.internal_error_code === 'invalid_input_to_karza') {
        setApiError('INCORRECT_OTP');
      }

      if (response?.fetchAadhaarXml === 'failed') {
        handleDownTimeError(response);
      }

      if (
        experiments.isDigilockerEkyc &&
        response?.is_success &&
        response?.fetchAadhaarXml !== 'failed'
      ) {
        const nextScreen = 'AadharSuccess';
        goToNextScreen({ nextScreen });
        analyticsTrack({
          objectName: 'SignUp',
          actionName: 'otp',
          screen: 'home page',
          eventAction: 'success',
          user,
          properties: {
            aadhaar_ekyc_mode: experiments.isDigilockerEkyc ? 'Digilocker native' : 'UIDAI native',
          },
        });
      } else if (
        experiments.isDigilockerEkyc &&
        !response?.success &&
        response.data.fetchAadhaarXml === 'failed' &&
        response?.data?.code !== 'resource_exhausted'
      ) {
        analyticsTrack({
          objectName: 'SignUp',
          actionName: 'otp',
          screen: 'home page',
          eventAction: 'failure',
          user,
          properties: {
            aadhaar_ekyc_mode: experiments.isDigilockerEkyc ? 'Digilocker native' : 'UIDAI native',
          },
        });
        handleDownTimeError(response);
      }

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
          properties: {
            aadhaar_ekyc_mode: experiments.isDigilockerEkyc ? 'Digilocker native' : 'UIDAI native',
          },
        });
      } else if (response.error_code) {
        analyticsTrack({
          objectName: 'SignUp',
          actionName: 'otp',
          screen: 'home page',
          eventAction: 'failure',
          user,
          properties: {
            aadhaar_ekyc_mode: experiments.isDigilockerEkyc ? 'Digilocker native' : 'UIDAI native',
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
      if (err && err.response && err.response.errors[0]?.includes('Internal Server Error')) {
        handleDownTimeError(err.response);
      }
    },
  });

  const handleSubmit = async (payload) => {
    const randomPin = Math.floor(1000 + Math.random() * 9000).toString();
    const data = experiments.isDigilockerEkyc
      ? {
          aadhaar_number: payload.aadharNumber,
          otp: payload.enteredOTP,
          request_id: requestId,
        }
      : {
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
                Aadhaar Verification ( via OTP )
              </Text>
              <Text size="xsmall" color="shade.950">
                An OTP will be sent to number linked with your Aadhaar
              </Text>
              <Space margin={[4, 0, 4, 0]}>
                <View>
                  <TextInput
                    width="auto"
                    name="aadharNumber"
                    type="text"
                    label="12 Digit Aadhaar Number"
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
                helpText="An OTP has been sent to mobile number linked with your Aadhaar"
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
                    properties: {
                      aadhaar_ekyc_mode: experiments.isDigilockerEkyc
                        ? 'Digilocker native'
                        : 'UIDAI native',
                    },
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
                    By verifying, you consent to share your aadhaar details with us and agree to{' '}
                    <Link
                      size="xsmall"
                      href="https://razorpay.com/privacy/"
                      target="_blank"
                      rel="noreferrer noopener"
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
