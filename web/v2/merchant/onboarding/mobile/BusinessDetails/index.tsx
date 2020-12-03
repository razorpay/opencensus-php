import React from 'react';
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
import { FormSection, Field } from '../Form';
import { useActivationFormState, isVisible, isTabComplete } from '../context/store';
import useActivation from '../hooks/useActivation';

const StyledSeparator = styled(View)`
  height: 1px;
  width: 100%;
  background-color: rgba(22, 47, 86, 0.05);
`;

const businessDetailsSchema = Yup.object().shape({
  company_pan: Yup.string()
    .trim()
    .length(10, 'PAN card must be 10 characters.')
    .matches(/^[a-zA-z]{5}\d{4}[a-zA-Z]{1}$/, {
      message: 'Invalid PAN Card.',
      excludeEmptyString: true,
    })
    .test(
      'companypan',
      'Invalid PAN format.',
      (value) => ['C', 'H', 'F', 'A', 'T', 'B', 'J', 'G', 'L'].indexOf(value[3]) !== -1,
    )
    .required('Company PAN is a required field'),
  business_name: Yup.string().required('Business Name is a required field'),
  promoter_pan: Yup.string()
    .trim()
    .length(10, 'PAN must be 10 characters.')
    .matches(/^[a-zA-z]{5}\d{4}[a-zA-Z]{1}$/, {
      message: 'Invalid PAN Card.',
      excludeEmptyString: true,
    })
    .test('promoter_pan', 'Invalid PAN format.', (value) => value[3] === 'P')
    .required('Promoter PAN is a required field'),
  promoter_pan_name: Yup.string().required('Promoter PAN Name is a required field'),
  business_registered_address: Yup.string().required('Registered address is a required field'),
  business_registered_state: Yup.string().required('Registered state is a required field'),
  business_registered_city: Yup.string().required('Registered city is a required field'),
  business_registered_pin: Yup.string()
    .trim()
    .length(6, 'Please enter a 6 digit pincode')
    .required('Registered PIN is a required field'),
  business_operation_address: Yup.string().required('Operation Address is a required field'),
  business_operation_state: Yup.string().required('Operation State is a required field'),
  business_operation_city: Yup.string().required('Operation City is a required field'),
  business_operation_pin: Yup.string()
    .trim()
    .length(6, 'Please enter a 6 digit pincode')
    .required('Operation PIN is a required fiel'),
});

const getData = (formikProps) => {
  const updatedBusinessDetails = {
    company_pan: {
      value: formikProps.values.company_pan,
      error: formikProps.errors.company_pan,
    },
    business_name: {
      value: formikProps.values.business_name,
      error: formikProps.errors.business_name,
    },
    promoter_pan: {
      value: formikProps.values.promoter_pan,
      error: formikProps.errors.promoter_pan,
    },
    promoter_pan_name: {
      value: formikProps.values.promoter_pan_name,
      error: formikProps.errors.promoter_pan_name,
    },
    business_registered_address: {
      value: formikProps.values.business_registered_address,
      error: formikProps.errors.business_registered_address,
    },
    business_registered_pin: {
      value: formikProps.values.business_registered_pin,
      error: formikProps.errors.business_registered_pin,
    },
    business_registered_city: {
      value: formikProps.values.business_registered_city,
      error: formikProps.errors.business_registered_city,
    },
    business_registered_state: {
      value: formikProps.values.business_registered_state,
      error: formikProps.errors.business_registered_state,
    },
    business_operation_address: {
      value: formikProps.values.business_operation_address,
      error: formikProps.errors.business_operation_address,
    },
    business_operation_pin: {
      value: formikProps.values.business_operation_pin,
      error: formikProps.errors.business_operation_pin,
    },
    business_operation_city: {
      value: formikProps.values.business_operation_city,
      error: formikProps.errors.business_operation_city,
    },
    business_operation_state: {
      value: formikProps.values.business_operation_state,
      error: formikProps.errors.business_operation_state,
    },
  };
  return updatedBusinessDetails;
};

const BusinessDetails: React.FC = () => {
  const { data, postData } = useActivation();
  const businessDetails = data.business_details;
  const setBusinessDetailsCompleted = useActivationFormState(
    (state) => state.setBusinessDetailsCompleted,
  );
  const setSameAddress = useActivationFormState((state) => state.setSameAddress);

  const copySameAddress = (values, formikProps) => {
    return {
      ...values,
      business_operation_address: {
        value: formikProps.values.business_registered_address,
        error: formikProps.errors.business_registered_address,
      },
      business_operation_pin: {
        value: formikProps.values.business_registered_pin,
        error: formikProps.errors.business_registered_pin,
      },
      business_operation_city: {
        value: formikProps.values.business_registered_city,
        error: formikProps.errors.business_registered_city,
      },
      business_operation_state: {
        value: formikProps.values.business_registered_state,
        error: formikProps.errors.business_registered_state,
      },
    };
  };

  const handleSameAddress = (checked, formikProps) => {
    formikProps.setFieldValue('same_address', checked);
    setSameAddress(checked);
    let updatedBusinessDetails = getData(formikProps);
    if (checked) {
      updatedBusinessDetails = copySameAddress(updatedBusinessDetails, formikProps);
    }
    const isComplete = isTabComplete(
      { ...data, business_details: updatedBusinessDetails },
      'business_details',
    );
    setBusinessDetailsCompleted(isComplete);
  };

  const handleBlur = (e, formikProps) => {
    let updatedBusinessDetails = getData(formikProps);
    if (formikProps.values.same_address) {
      updatedBusinessDetails = copySameAddress(updatedBusinessDetails, formikProps);
    }
    const isComplete = isTabComplete(
      { ...data, business_details: updatedBusinessDetails },
      'business_details',
    );
    setBusinessDetailsCompleted(isComplete);
    if (isComplete) {
      postData(updatedBusinessDetails);
    }
    formikProps.handleBlur(e);
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
            subtitle="These details will be verified with the government database"
          >
            <Field visible={isVisible('company_pan', data)}>
              <TextInput
                width="auto"
                name="company_pan"
                label="Busines PAN"
                helpText="PAN of the Company"
                value={formikProps.values.company_pan}
                errorText={formikProps.errors.company_pan}
              />
            </Field>
            <Field visible={isVisible('business_name', data)}>
              <TextInput
                width="auto"
                name="business_name"
                label="Business Name"
                helpText="As mentioned in the PAN"
                value={formikProps.values.business_name}
                errorText={formikProps.errors.business_name}
              />
            </Field>
            <Field>
              <TextInput
                width="auto"
                name="promoter_pan"
                label="Business Owner's PAN"
                value={formikProps.values.promoter_pan}
                errorText={formikProps.errors.promoter_pan}
              />
            </Field>
            <Field last>
              <TextInput
                width="auto"
                name="promoter_pan_name"
                label="Business Owner's Name"
                helpText="As mentioned in the PAN"
                value={formikProps.values.promoter_pan_name}
                errorText={formikProps.errors.promoter_pan_name}
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
                errorText={formikProps.errors.business_registered_address}
              />
            </Field>
            <Field>
              <TextInput
                width="auto"
                name="business_registered_pin"
                label="Pincode"
                value={formikProps.values.business_registered_pin}
                errorText={formikProps.errors.business_registered_pin}
              />
            </Field>
            <Field>
              <TextInput
                width="auto"
                name="business_registered_city"
                label="City"
                value={formikProps.values.business_registered_city}
                errorText={formikProps.errors.business_registered_city}
              />
            </Field>
            <Field>
              <TextInput
                width="auto"
                name="business_registered_state"
                label="Select State"
                value={formikProps.values.business_registered_state}
                errorText={formikProps.errors.business_registered_state}
              />
            </Field>
            <Field visible={isVisible('business_operation_address', data)} last>
              <Checkbox
                name="same_address"
                title="Operational address is the same as above"
                helpText="Physical verification may take place"
                checked={formikProps.values.same_address}
                onChange={(value) => handleSameAddress(value, formikProps)}
              />
            </Field>
          </FormSection>

          {!formikProps.values.same_address && isVisible('business_operation_address', data) ? (
            <FormSection title="Business Operational Address" last>
              <Field>
                <TextArea
                  width="auto"
                  name="business_operation_address"
                  label="Enter Address"
                  value={formikProps.values.business_operation_address}
                  errorText={formikProps.errors.business_operation_address}
                />
              </Field>
              <Field>
                <TextInput
                  width="auto"
                  name="business_operation_pin"
                  label="Pincode"
                  value={formikProps.values.business_operation_pin}
                  errorText={formikProps.errors.business_operation_pin}
                />
              </Field>
              <Field>
                <TextInput
                  width="auto"
                  name="business_operation_city"
                  label="City"
                  value={formikProps.values.business_operation_city}
                  errorText={formikProps.errors.business_operation_city}
                />
              </Field>
              <Field last>
                <TextInput
                  width="auto"
                  name="business_operation_state"
                  label="Select State"
                  value={formikProps.values.business_operation_state}
                  errorText={formikProps.errors.business_operation_state}
                />
              </Field>
            </FormSection>
          ) : null}

          <Space margin={[2, 0, 1.5, 0]}>
            <StyledSeparator />
          </Space>

          <Text size="xsmall" align="center">
            By submitting these details you agree to our{' '}
            <Link size="xsmall">terms and conditions</Link>
          </Text>
        </form>
      )}
    </Formik>
  );
};

export default BusinessDetails;
