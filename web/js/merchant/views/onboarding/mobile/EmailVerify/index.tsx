import React, { useState, useEffect } from 'react';
import { Formik } from 'formik';
import * as Yup from 'yup';
import { useMutation } from 'react-query';
import { fetch } from 'common/services/rest/rest-fetch';
import TextInput from '@razorpay/blade-old/src/atoms/TextInput';
import styled from 'styled-components';
import View from '@razorpay/blade-old/src/atoms/View';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Button from '@razorpay/blade-old/src/atoms/Button';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import Text from '@razorpay/blade-old/src/atoms/Text';
import { FormSection, Field } from '../Form';
import { useActivationFormState } from '../context/store';
import useActivation from '../hooks/useActivation';
import usePartnerActivation from '../hooks/usePartnerActivation';
import { useApp } from 'common/context/App';
import VerifyOTP from './VerifyOTP';

interface IEmailVerifyProps {
  isFormLocked?: boolean;
  contactName?: boolean;
}

const getOTP = async (data) => {
  const fetchData = await fetch<any>({
    url: 'merchant/activation/otp/send',
    method: 'POST',
    data,
    mode: 'live',
  });
  return fetchData;
};

const StyledView = styled(View)`
  opacity: ${({ disable }) => (disable ? '0.7' : '1')};
  pointer-events: ${({ disable }) => (disable ? 'none' : 'all')};
`;

const EmailVerify = ({ isFormLocked, contactName }: IEmailVerifyProps): React.ReactElement => {
  const { data } = useActivation();
  const {
    user,
    experiments: { isEmailNonMandatoryOnL1, isEmailNonMandatoryOnL2Form },
  } = useApp();
  const { getFieldStatus } = usePartnerActivation();
  const contactDetails = data.contact_details;

  const [isApiCalling, setIsApiCall] = useState<boolean>(false);
  const [apiError, setApiError] = useState<string>('');
  const [token, setToken] = useState<string>('');
  const [isOtpSend, setIsOtpSend] = useState<boolean>(false);

  const setContactDetailsCompleted = useActivationFormState(
    (state) => state.setContactDetailsCompleted,
  );
  const setL1Acknowledge = useActivationFormState((state) => state.setL1Acknowledge);
  const [isEmailVerified, setIsEmailVerified] = useState(user?.user?.confirmed);

  const emailVerified = (isVerified: boolean) => {
    setIsEmailVerified(isVerified);
    setIsOtpSend(false);
    setContactDetailsCompleted(true);
  };

  const resendOTP = () => setIsOtpSend(false);

  const [sendOTP] = useMutation(getOTP, {
    onSuccess: (res: { token: string }) => {
      setIsApiCall(false);
      if (res.token) {
        setToken(res.token);
        setIsOtpSend(true);
      }
    },
    onError: (err: { response: { errors: Array<string> } }) => {
      setIsApiCall(false);
      setApiError(err.response.errors[0]);
    },
  });

  const submitOTP = async (formikProps) => {
    if (!formikProps.values.contact_email) {
      formikProps.setErrors({
        contact_email: 'Contact Email is a required field.',
      });
      return;
    }
    const payload = token
      ? { email: formikProps.values.contact_email, token }
      : { email: formikProps.values.contact_email };
    setIsApiCall(true);
    await sendOTP(payload);
  };

  useEffect(() => {
    if (isOtpSend && !isEmailVerified && isEmailNonMandatoryOnL1) {
      setContactDetailsCompleted(false);
    }
  }, [isOtpSend]);

  return (
    <Formik
      initialValues={{
        contact_email:
          contactDetails.contact_email.value || user?.contact_email || data?.contact_email,
      }}
      validationSchema={() => {
        const _schema = Yup.object().shape({
          contact_email: Yup.lazy(() => {
            if (isEmailNonMandatoryOnL1) {
              return Yup.string()
                .email('Unable to send OTP. Kindly check the entered email')
                .nullable();
            }
            return Yup.string()
              .email('Unable to send OTP. Kindly check the entered email')
              .required('Contact Email is a required field.')
              .nullable();
          }),
        });
        return _schema;
      }}
      validateOnMount={true}
      onSubmit={(e) => {
        console.log('onSubmit', e);
      }}
    >
      {(formikProps) => (
        <FormSection
          title=""
          padding={[0]}
          visible={
            !data?.activation_form_milestone || isEmailVerified || isEmailNonMandatoryOnL2Form
          }
        >
          <Field last>
            <TextInput
              width="auto"
              name="contact_email"
              label="Contact Email"
              helpText={
                !isEmailNonMandatoryOnL2Form
                  ? getFieldStatus('contact_name').description ||
                    'All important communications and account updates will be sent to this email ID'
                  : ''
              }
              value={formikProps.values.contact_email}
              errorText={
                apiError || (formikProps.touched.contact_email && formikProps.errors.contact_email)
              }
              onChange={(value) => {
                formikProps.setFieldValue('contact_email', value.trim());
                if (apiError) {
                  setApiError('');
                }
                if (isEmailNonMandatoryOnL1) {
                  setL1Acknowledge(false);
                  if (value && value.length) {
                    setContactDetailsCompleted(false);
                  } else if (value === '' && contactName) {
                    setContactDetailsCompleted(true);
                  }
                }
              }}
              disabled={
                isFormLocked ||
                getFieldStatus('contact_email').isDisabled ||
                isOtpSend ||
                isEmailVerified ||
                (data?.activation_form_milestone && isEmailNonMandatoryOnL1)
              }
              iconRight={isEmailVerified ? 'check' : ''}
              onBlur={() => {
                formikProps.setFieldTouched('contact_email');
              }}
            />
          </Field>
          {isEmailVerified && isEmailNonMandatoryOnL2Form && (
            <Space padding={[0.5, 0, 0]}>
              <Text color="positive.960" size="small">
                We have verified your email successfully.{' '}
              </Text>
            </Space>
          )}
          {!data?.activation_form_milestone || isEmailNonMandatoryOnL2Form ? (
            <>
              {!isEmailVerified && !isOtpSend && (
                <Flex alignItems="flex-start" flexDirection="column">
                  <StyledView>
                    <Space padding={[1.5, 0]}>
                      <View>
                        {isApiCalling ? (
                          <Button variant="secondary" size="small" type="submit">
                            Sending OTP ...
                          </Button>
                        ) : (
                          <Button
                            variant="secondary"
                            size="small"
                            name="contact_email"
                            type="button"
                            icon="chevronRight"
                            iconAlign="right"
                            onClick={() => submitOTP(formikProps)}
                            disabled={!formikProps.values.contact_email}
                            children="Verify With OTP"
                          />
                        )}
                      </View>
                    </Space>
                    {(isEmailNonMandatoryOnL1 || isEmailNonMandatoryOnL2Form) && (
                      <Text size="small" color="shade.950">
                        You email will not be saved till it’s verified.
                      </Text>
                    )}
                  </StyledView>
                </Flex>
              )}
              {isOtpSend && !isEmailVerified ? (
                <VerifyOTP
                  token={token}
                  emailVerified={emailVerified}
                  resendOTP={resendOTP}
                  contactEmail={formikProps.values.contact_email}
                />
              ) : null}
            </>
          ) : null}
        </FormSection>
      )}
    </Formik>
  );
};

export default EmailVerify;
