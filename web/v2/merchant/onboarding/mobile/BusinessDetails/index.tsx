import React, { useState } from 'react';
import styled from 'styled-components';
import * as Yup from 'yup';
import { Formik } from 'formik';
import View from '@razorpay/blade/src/atoms/View';
import Space from '@razorpay/blade/src/atoms/Space';
import Text from '@razorpay/blade/src/atoms/Text';
import TextInput from '@razorpay/blade/src/atoms/TextInput';
import TextArea from '@razorpay/blade/src/atoms/TextArea';
import Checkbox from '@razorpay/blade/src/atoms/Checkbox';
import Link from '@commander/shield/src/shared/Link';
import { getColor } from '@razorpay/blade/src/_helpers/theme';
import { Select, Option } from 'v2/components/Select';
import { FormSection, Field, GetTouchedFields } from '../Form';
import { useActivationFormState, isVisible, isTabComplete } from '../context/store';
import useActivation, { getRequestData } from '../hooks/useActivation';
import { getLabel, getHelpText, getPoiVerificationStatus } from '../services/utils';
import { states } from '../Constants/OnboardingConstants';

const StyledSeparator = styled(View)`
  height: 1px;
  width: 100%;
  background-color: ${({ theme }) => getColor(theme, 'shade.920')};
`;

const businessDetailsSchema = Yup.object().shape({
  company_pan: Yup.string()
    .trim()
    .length(10, 'PAN card must be 10 characters')
    .matches(/^[a-zA-z]{5}\d{4}[a-zA-Z]{1}$/, {
      message: 'Invalid PAN Card',
      excludeEmptyString: true,
    })
    .test('companypan', 'Invalid PAN format.', (value) => {
      if (!value) {
        return true;
      }
      return ['C', 'H', 'F', 'A', 'T', 'B', 'J', 'G', 'L'].indexOf(value[3].toUpperCase()) !== -1;
    })
    .required('Company PAN is a required field')
    .nullable(),
  business_name: Yup.string().required('Business Name is a required field').nullable(),
  promoter_pan: Yup.string()
    .trim()
    .length(10, 'PAN must be 10 characters')
    .matches(/^[a-zA-z]{5}\d{4}[a-zA-Z]{1}$/, {
      message: 'Invalid PAN Card.',
      excludeEmptyString: true,
    })
    .test('promoter_pan', 'Invalid PAN Card', (value) => {
      if (!value) {
        return true;
      }
      return value[3].toLowerCase() === 'p';
    })
    .required('Promoter PAN is a required field')
    .nullable(),
  promoter_pan_name: Yup.string().required('Promoter PAN Name is a required field').nullable(),
  business_registered_address: Yup.string()
    .required('Registered address is a required field')
    .nullable(),
  business_registered_state: Yup.string()
    .required('Registered state is a required field')
    .nullable(),
  business_registered_city: Yup.string().required('Registered city is a required field').nullable(),
  business_registered_pin: Yup.string()
    .trim()
    .length(6, 'Please enter a 6 digit pincode')
    .required('Registered PIN is a required field')
    .nullable(),
  business_operation_address: Yup.string()
    .required('Operation Address is a required field')
    .nullable(),
  business_operation_state: Yup.string().required('Operation State is a required field').nullable(),
  business_operation_city: Yup.string().required('Operation City is a required field').nullable(),
  business_operation_pin: Yup.string()
    .trim()
    .length(6, 'Please enter a 6 digit pincode')
    .required('Operation PIN is a required field')
    .nullable(),
});

const BusinessDetails: React.FC = () => {
  const { data, postData } = useActivation();
  const businessDetails = data.business_details;
  const setBusinessDetailsCompleted = useActivationFormState(
    (state) => state.setBusinessDetailsCompleted,
  );
  const setSameAddress = useActivationFormState((state) => state.setSameAddress);
  const hasSameAdress = useActivationFormState((state) => state.same_address);
  const [isBlurCalled, setIsBlurCalled] = useState(false);

  const isUnregPoiStatus = getPoiVerificationStatus(data);

  const copySameAddress = (reqData, updatedDetails) => {
    let _reqData = { ...reqData };
    const addressFieldKeys = [
      'business_registered_address',
      'business_registered_state',
      'business_registered_city',
      'business_registered_pin',
    ];
    addressFieldKeys.forEach((key) => {
      const operationAddressKey = key.replace('registered', 'operation');
      if (updatedDetails[key]) {
        _reqData = {
          ..._reqData,
          [operationAddressKey]: {
            value: updatedDetails[key].value,
          },
        };
      } else {
        _reqData = {
          ..._reqData,
          [operationAddressKey]: {
            value: businessDetails[key].value,
          },
        };
      }
    });
    return _reqData;
  };

  const handleSameAddress = (checked) => {
    setSameAddress(checked);
    setIsBlurCalled(true);
  };

  const handleBlur = (e, formikProps) => {
    formikProps.handleBlur(e);
    setIsBlurCalled(true);
  };

  const handleSubmit = (updatedDetails) => {
    let reqData;
    const isComplete = isTabComplete(
      { ...data, business_details: { ...businessDetails, ...updatedDetails }, hasSameAdress },
      'business_details',
    );
    setBusinessDetailsCompleted(isComplete);
    if (hasSameAdress) {
      reqData = copySameAddress(reqData, updatedDetails);
      updatedDetails = { ...reqData, ...updatedDetails };
    }
    reqData = getRequestData(businessDetails, updatedDetails);
    if (Object.keys(reqData).length) {
      postData(reqData);
    }
  };

  return (
    <Formik
      initialValues={{
        company_pan: businessDetails.company_pan.value,
        business_name: businessDetails.business_name.value,
        promoter_pan: businessDetails.promoter_pan.value,
        promoter_pan_name: businessDetails.promoter_pan_name.value,
        business_registered_address: businessDetails.business_registered_address.value,
        business_registered_state: businessDetails.business_registered_state.value,
        business_registered_city: businessDetails.business_registered_city.value,
        business_registered_pin: businessDetails.business_registered_pin.value,
        business_operation_address: businessDetails.business_operation_address.value,
        business_operation_state: businessDetails.business_operation_state.value,
        business_operation_city: businessDetails.business_operation_city.value,
        business_operation_pin: businessDetails.business_operation_pin.value,
        same_address: false,
      }}
      validationSchema={businessDetailsSchema}
      enableReinitialize
      onSubmit={() => console.log('onSubmit')}
    >
      {(formikProps) => (
        <form
          onChange={formikProps.handleChange}
          onBlur={(e) => {
            handleBlur(e, formikProps);
          }}
        >
          <FormSection
            title="PAN Details"
            subtitle={
              isUnregPoiStatus
                ? 'PAN Verification failed. Please review your details and submit again'
                : 'These details will be verified with the government database'
            }
            hasError={isUnregPoiStatus}
          >
            <Field visible={isVisible('company_pan', data)}>
              <TextInput
                width="auto"
                name="company_pan"
                label="Busines PAN"
                helpText="PAN of the Company"
                value={formikProps.values.company_pan}
                errorText={formikProps.touched.company_pan && formikProps.errors.company_pan}
              />
            </Field>
            <Field visible={isVisible('business_name', data)}>
              <TextInput
                width="auto"
                name="business_name"
                label="Business Name"
                helpText="As mentioned in the PAN"
                value={formikProps.values.business_name}
                errorText={formikProps.touched.business_name && formikProps.errors.business_name}
              />
            </Field>
            <Field>
              <TextInput
                width="auto"
                name="promoter_pan"
                label={getLabel('promoter_pan', data)}
                helpText={getHelpText('promoter_pan', data)}
                value={formikProps.values.promoter_pan}
                errorText={formikProps.touched.promoter_pan && formikProps.errors.promoter_pan}
              />
            </Field>
            <Field last>
              <TextInput
                width="auto"
                name="promoter_pan_name"
                label={getLabel('promoter_pan_name', data)}
                helpText="As mentioned in the PAN"
                value={formikProps.values.promoter_pan_name}
                errorText={
                  formikProps.touched.promoter_pan_name && formikProps.errors.promoter_pan_name
                }
              />
            </Field>
          </FormSection>

          <FormSection
            title="Address Details"
            subtitle="These details will be verified with the government database"
          >
            <Field>
              <TextArea
                width="auto"
                name="business_registered_address"
                label="Enter Address"
                value={formikProps.values.business_registered_address}
                errorText={
                  formikProps.touched.business_registered_address &&
                  formikProps.errors.business_registered_address
                }
              />
            </Field>
            <Field>
              <TextInput
                width="auto"
                name="business_registered_pin"
                label="Pincode"
                value={formikProps.values.business_registered_pin}
                errorText={
                  formikProps.touched.business_registered_pin &&
                  formikProps.errors.business_registered_pin
                }
              />
            </Field>
            <Field>
              <TextInput
                width="auto"
                name="business_registered_city"
                label="City"
                value={formikProps.values.business_registered_city}
                errorText={
                  formikProps.touched.business_registered_city &&
                  formikProps.errors.business_registered_city
                }
              />
            </Field>
            <Field>
              <Select
                label="Select State"
                placeholder="SELECT STATE"
                inputPlaceholder="Search State"
                searchable={true}
                errorText={
                  formikProps.touched.business_registered_state &&
                  formikProps.errors.business_registered_state
                }
                value={formikProps.values.business_registered_state}
                onChange={(value) => {
                  formikProps.setFieldTouched('business_registered_state');
                  formikProps.setFieldValue('business_registered_state', value);
                  setIsBlurCalled(true);
                }}
              >
                {Object.keys(states).map((state_code) => (
                  <Option key={state_code} value={state_code} label={states[state_code]}>
                    {states[state_code]}
                  </Option>
                ))}
              </Select>
            </Field>
            <Field visible={isVisible('business_operation_address', data)} last>
              <Checkbox
                name="same_address"
                title="Operational address is the same as above"
                helpText="Physical verification may take place"
                defaultChecked={hasSameAdress}
                onChange={(value) => handleSameAddress(value)}
              />
            </Field>
          </FormSection>

          {!hasSameAdress ? (
            <FormSection title="Business Operational Address" last>
              <Field>
                <TextArea
                  width="auto"
                  name="business_operation_address"
                  label="Enter Address"
                  value={formikProps.values.business_operation_address}
                  errorText={
                    formikProps.touched.business_operation_address &&
                    formikProps.errors.business_operation_address
                  }
                />
              </Field>
              <Field>
                <TextInput
                  width="auto"
                  name="business_operation_pin"
                  label="Pincode"
                  value={formikProps.values.business_operation_pin}
                  errorText={
                    formikProps.touched.business_operation_pin &&
                    formikProps.errors.business_operation_pin
                  }
                />
              </Field>
              <Field>
                <TextInput
                  width="auto"
                  name="business_operation_city"
                  label="City"
                  value={formikProps.values.business_operation_city}
                  errorText={
                    formikProps.touched.business_operation_city &&
                    formikProps.errors.business_operation_city
                  }
                />
              </Field>
              <Field last>
                <Select
                  label="Select State"
                  placeholder="SELECT STATE"
                  inputPlaceholder="Search State"
                  searchable={true}
                  errorText={
                    formikProps.touched.business_operation_state &&
                    formikProps.errors.business_operation_state
                  }
                  value={formikProps.values.business_operation_state}
                  onChange={(value) => {
                    formikProps.setFieldTouched('business_operation_state');
                    formikProps.setFieldValue('business_operation_state', value);
                    setIsBlurCalled(true);
                  }}
                >
                  {Object.keys(states).map((state_code) => (
                    <Option key={state_code} value={state_code} label={states[state_code]}>
                      {states[state_code]}
                    </Option>
                  ))}
                </Select>
              </Field>
            </FormSection>
          ) : null}

          <Space margin={[2, 0, 1.5, 0]}>
            <StyledSeparator />
          </Space>
          {data.activation_flow !== 'greylist' ? (
            <Text size="xsmall" align="center">
              By submitting these details you agree to our{' '}
              <Link href="https://razorpay.com/terms/" target="_blank" size="xsmall">
                terms and conditions
              </Link>
            </Text>
          ) : null}

          <GetTouchedFields
            handleSubmit={handleSubmit}
            isBlurCalled={isBlurCalled}
            setIsBlurCalled={setIsBlurCalled}
          />
        </form>
      )}
    </Formik>
  );
};

export default BusinessDetails;
