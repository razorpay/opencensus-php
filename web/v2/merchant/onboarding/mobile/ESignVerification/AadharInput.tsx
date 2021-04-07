import React, { useState } from 'react';
import * as Yup from 'yup';
import { Formik, Form } from 'formik';
import Text from '@razorpay/blade/src/atoms/Text';
import View from '@razorpay/blade/src/atoms/View';
import Space from '@razorpay/blade/src/atoms/Space';
import TextInput from '@razorpay/blade/src/atoms/TextInput';
import Button from '@razorpay/blade/src/atoms/Button';
import Link from '@commander/shield/src/shared/Link';
import Checkbox from '@razorpay/blade/src/atoms/Checkbox';
import useActivation from '../hooks/useActivation';
import { Divider, StyledView } from './Styled';
import { analyticsTrack } from '../../../../services/tracking/segment';
import { useApp } from 'v2/context/App';

interface AadharInputPropsT {
  goToNextScreen: ({ nextScreen: string }) => void;
  setAadharNumber: (data: string) => void;
  setAadharInputError: (data: string) => void;
  aadharError: string;
  disabled: boolean;
}

const AadharInput: React.FC<AadharInputPropsT> = ({
  goToNextScreen,
  setAadharNumber,
  setAadharInputError,
  aadharError,
  disabled,
}) => {
  const { postData, data } = useActivation();
  const { user } = useApp();
  const [isAadharLinkedToMobile, setIsaadharLinkedToMobile] = useState(
    data && data.stakeholder ? !!data.stakeholder.aadhaar_linked : true,
  );
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
    });
  };
  return (
    <Formik
      initialValues={{
        aadharNumber: '',
      }}
      validationSchema={() => {
        return Yup.object().shape({
          aadharNumber: Yup.string()
            .length(12, 'Aadhar should be of 12 digits')
            .required('Aadhar Number is a required field'),
        });
      }}
      onSubmit={(values) => {
        const nextScreen = 'GetOTP';
        setAadharNumber(values.aadharNumber);
        goToNextScreen({ nextScreen });
      }}
    >
      {(formikProps) => (
        <Form>
          <Space>
            <View>
              <Text size="medium" weight="bold" color="shade.970">
                Aadhar Verification
              </Text>
              <Text size="xsmall" color="shade.950">
                An OTP will be sent to number linked with your Aadhar
              </Text>

              <Space margin={[4, 0, 0, 0]}>
                <StyledView disable={!isAadharLinkedToMobile || disabled}>
                  <TextInput
                    width="auto"
                    name="aadharNumber"
                    type="text"
                    label="12 Digit Aadhar Number"
                    value={formikProps.values.aadharNumber}
                    errorText={
                      aadharError === 'MOBILE_NOT_LINKED'
                        ? 'This Aadhar is not linked to any number'
                        : formikProps.touched.aadharNumber && formikProps.errors.aadharNumber
                    }
                    onChange={(value) => {
                      formikProps.setFieldValue('aadharNumber', value.trim());
                      if (aadharError) {
                        setAadharInputError('');
                      }
                    }}
                    onBlur={(value) => {
                      formikProps.setFieldTouched('aadharNumber', value.trim());
                      analyticsTrack({
                        objectName: 'SignUp',
                        actionName: 'Aadhar number',
                        screen: 'home page',
                        eventAction: 'initiated',
                        user,
                      });
                    }}
                  />
                  <Space padding={[1.5, 0]}>
                    <View>
                      <Button
                        variant="secondary"
                        size="small"
                        type="submit"
                        disabled={!isAadharLinkedToMobile || disabled}
                        icon="chevronRight"
                        iconAlign="right"
                      >
                        Verify with OTP
                      </Button>
                    </View>
                  </Space>
                  <Space padding={[0, 0, 2]}>
                    <View>
                      <Text size="small" color="shade.960">
                        By verifying, you consent to share your aadhar details with us and agree to{' '}
                        <Link
                          size="small"
                          href="https://razorpay.com/privacy/"
                          target="_blank"
                          onClick={() => {}}
                          disabled={!isAadharLinkedToMobile || disabled}
                        >
                          privacy policy
                        </Link>{' '}
                      </Text>
                    </View>
                  </Space>
                </StyledView>
              </Space>
              <Space margin={[0, 0, 1.5]}>
                <Divider />
              </Space>
              <Checkbox
                onChange={mobileNotLinked}
                title="My Aadhar is not linked with any mobile number"
                checked={!isAadharLinkedToMobile}
                disabled={disabled}
              />
              {!isAadharLinkedToMobile && (
                <Text size="small" color="shade.960">
                  You can continue without verification but your details review might take a little
                  longer
                </Text>
              )}
            </View>
          </Space>
        </Form>
      )}
    </Formik>
  );
};

export default AadharInput;
