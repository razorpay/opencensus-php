import React, { useState } from 'react';
import styled from 'styled-components';
import { useMutation } from '@tanstack/react-query';
import { connect } from 'react-redux';
import { Formik, Form } from 'formik';
import * as Yup from 'yup';
import TextInput from '@razorpay/blade-old/src/atoms/TextInput';
import View from '@razorpay/blade-old/src/atoms/View';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Button from '@razorpay/blade-old/src/atoms/Button';
import { fetch } from 'common/services/rest/rest-fetch';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import { Field, FormSection } from 'merchant/views/onboarding/mobile/Form';
import { useSnackbar } from 'common/components/SnackBar/SnackbarContext';
import useActivation from 'merchant/views/onboarding/mobile/hooks/useActivation';
import Text from '@razorpay/blade-old/src/atoms/Text';
import { updateSession } from 'merchant/reducers/session';
import User from 'merchant/models/User';
import { useApp } from 'common/context/App';
import useTrackEvents from 'merchant/hooks/useTrackEvents';

type ObjType = Record<string, unknown>;
interface IVerifyOTPProps {
  token: string;
  emailVerified: (isVerified: boolean) => void;
  resendOTP?: () => void;
  updateSessions: (payload) => ObjType;
  contactEmail?: string;
  showFormSection?: boolean;
}

const StyledView = styled(View)`
  opacity: ${({ disable }) => (disable ? '0.7' : '1')};
  pointer-events: ${({ disable }) => (disable ? 'none' : 'all')};
`;

const VerifyOTP = ({
  token,
  resendOTP,
  emailVerified,
  updateSessions,
  contactEmail = '',
  showFormSection = false,
}: IVerifyOTPProps): React.ReactElement => {
  const { data, postData } = useActivation();
  const snackbar = useSnackbar();
  const { user, experiments } = useApp();
  const [error, setError] = useState<string>('');
  const trackEvents = useTrackEvents();

  const verifyOtp = async (payload: { otp: string; token: string }) => {
    trackEvents({
      objectName: 'Verify',
      actionName: 'email',
      eventAction: 'request',
      screen: 'contact tab',
      activationType: !data.activation_form_milestone ? 'act' : 'kyc',
      properties: {
        status: 'success',
      },
    });
    const result = await fetch<any>({
      url: 'users/verify_email',
      method: 'POST',
      data: payload,
    });
    return result;
  };

  const { mutate: verify } = useMutation({
    mutationFn: verifyOtp,
    onSuccess: async (res: {
      user: {
        email_verified: boolean;
        email: string | null;
        confirmed: boolean;
        signup_via_email: number;
      };
    }) => {
      if (res.user?.email_verified) {
        const updateSelectedUserValue = {
          confirmed: res.user.confirmed,
          email: contactEmail || res.user.email,
          email_verified: res.user.email_verified,
          signup_via_email: res.user.signup_via_email,
        };

        const userData = new User({
          ...user,
          contact_email: contactEmail || res.user.email,
          user: { ...user.user, ...updateSelectedUserValue },
        });
        const verifiedEmail = contactEmail || res.user.email || '';
        await postData({ contact_email: verifiedEmail });
        updateSessions({ user: userData });
        snackbar.success('Your Email ID is now verified');
        emailVerified(true);
        trackEvents({
          objectName: 'Verify',
          actionName: 'email',
          eventAction: 'result',
          screen: 'contact tab',
          activationType: !data.activation_form_milestone ? 'act' : 'kyc',
          properties: {
            status: 'success',
          },
        });
      }
    },
    onError: (err: { response: { errors: Array<string> } }) => {
      setError(err.response.errors[0] || 'Invalid OTP. Try again');
      trackEvents({
        objectName: 'Verify',
        actionName: 'email',
        eventAction: 'result',
        screen: 'contact tab',
        activationType: !data.activation_form_milestone ? 'act' : 'kyc',
        properties: {
          status: 'failure',
          errorMessage: err.response.errors[0],
        },
      });
    },
  });

  const handleSubmit = async (payload: { otp: string }) => {
    trackEvents({
      objectName: 'Verify',
      actionName: 'email',
      eventAction: 'clicked',
      screen: 'contact tab',
      activationType: !data.activation_form_milestone ? 'act' : 'kyc',
      properties: {
        optional:
          experiments.isEmailNonMandatoryOnL1 || experiments.isEmailNonMandatoryOnL2Form
            ? 'Yes'
            : 'NO',
      },
    });

    const reqData = { otp: payload.otp, token };
    await verify(reqData);
  };

  return (
    <Formik
      initialValues={{ otp: '' }}
      validationSchema={() =>
        Yup.object().shape({
          otp: Yup.string()
            .trim()
            .length(6, 'Please enter a valid 6-digit OTP number.')
            .required('Enter OTP is required field'),
        })
      }
      validateOnMount={true}
      onSubmit={handleSubmit}
    >
      {(formikProps) => (
        <Form>
          <FormSection
            title={showFormSection ? 'Verify your email Id' : ''}
            subtitle={showFormSection ? `OTP is sent to ${contactEmail}` : ''}
            titleFontSize="large"
            subtitleFontSize="small"
            padding={[0]}
          >
            <Field last>
              <TextInput
                type="number"
                width="auto"
                name="otp"
                label="OTP"
                value={formikProps.values.otp}
                errorText={error || (formikProps.touched.otp && formikProps.errors.otp)}
                onChange={(value) => {
                  formikProps.setFieldValue('otp', value.trim());
                  if (error) {
                    setError('');
                  }
                }}
                helpText={showFormSection ? '' : 'An OTP has been sent to your email ID'}
                maxLength={6}
              />
            </Field>
            <Flex alignItems="center">
              <StyledView>
                <Space padding={[0, 0, 1]} margin={showFormSection ? [1.5, 0, 0] : [0.5, 0, 0]}>
                  <View>
                    {formikProps.isSubmitting ? (
                      <Button variant="secondary" size="small" type="submit">
                        Verifying ...
                      </Button>
                    ) : (
                      <Button
                        variant="secondary"
                        size="small"
                        type="submit"
                        icon="chevronRight"
                        iconAlign="right"
                        children="Submit OTP"
                        disabled={formikProps.values.otp.length !== 6}
                      />
                    )}
                  </View>
                </Space>
              </StyledView>
            </Flex>
            {!showFormSection && (
              <Flex alignItems="center">
                <View>
                  <Text size="small" color="shade.960">
                    Did not receive OTP?
                  </Text>
                  <Button variant="tertiary" size="small" onClick={resendOTP}>
                    Start again
                  </Button>
                </View>
              </Flex>
            )}
          </FormSection>
        </Form>
      )}
    </Formik>
  );
};

export default connect(null, { updateSessions: updateSession })(VerifyOTP);
