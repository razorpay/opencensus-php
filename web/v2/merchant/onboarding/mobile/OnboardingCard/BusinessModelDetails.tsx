import React from 'react';
import { Formik } from 'formik';
import * as Yup from 'yup';
import TextInput from '@razorpay/blade/src/atoms/TextInput';
import Space from '@razorpay/blade/src/atoms/Space';
import View from '@razorpay/blade/src/atoms/View';
import Button from '@razorpay/blade/src/atoms/Button';
import { Field } from '../Form';

const onboardingCardValidationSchema = Yup.object().shape({
  business_type: Yup.string().trim().required(),
  business_category: Yup.string().trim().required(),
  business_model: Yup.string().trim().required(),
});

interface BusinessModelDetailsPropsT {
  data: any;
}

const BusinessModelDetails: React.FC<BusinessModelDetailsPropsT> = ({ data }) => {
  if (!data.onboarding_milestone) {
    return (
      <Formik
        initialValues={{
          business_type: data.business_overview.business_type.value,
          business_category: data.business_overview.business_category.value,
          business_model: data.business_model,
        }}
        validationSchema={onboardingCardValidationSchema}
        onSubmit={() => console.log('onSubmit')}
      >
        {(formikProps) => {
          const isBusinessModelVisible =
            formikProps.values.business_category === 'I cannot find my category';
          return (
            <form onChange={formikProps.handleChange}>
              <Field>
                <TextInput
                  name="business_type"
                  label="Business Type"
                  width="auto"
                  value={formikProps.values.business_type}
                  errorText={formikProps.errors.business_type}
                />
              </Field>
              <Field last={!isBusinessModelVisible}>
                <TextInput
                  name="business_category"
                  label="Your Business Category"
                  width="auto"
                  value={formikProps.values.business_category}
                  errorText={formikProps.errors.business_category}
                  helpText="Business category cannot be changed once submitted"
                />
              </Field>
              <Field visible={isBusinessModelVisible} last>
                <TextInput
                  name="business_model"
                  label="Business Model"
                  width="auto"
                  value={formikProps.values.business_model}
                  errorText={formikProps.errors.business_model}
                  helpText="Tell us a bit about your business model"
                />
              </Field>
              <Space margin={[2.5, 0, 0, 0]}>
                <View>
                  <Button type="submit" block disabled={!formikProps.isValid}>
                    Start Activation
                  </Button>
                </View>
              </Space>
            </form>
          );
        }}
      </Formik>
    );
  }

  return null;
};

export default BusinessModelDetails;
