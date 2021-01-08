import React, { useState } from 'react';
import { Formik } from 'formik';
import * as Yup from 'yup';
import TextInput from '@razorpay/blade/src/atoms/TextInput';
import Text from '@razorpay/blade/src/atoms/Text';
import Space from '@razorpay/blade/src/atoms/Space';
import View from '@razorpay/blade/src/atoms/View';
import Button from '@razorpay/blade/src/atoms/Button';
import { withRouter, RouteComponentProps } from 'react-router-dom';
import { Field, GetTouchedFields } from '../Form';
import useActivation, { getRequestData } from '../hooks/useActivation';
import BusinessType from '../Fields/BusinessType';
import BusinessCategory from '../Fields/BusinessCategory';

interface BusinessModelDetailsI {
  business_type: string;
  business_subcategory: string;
  business_model: string;
}

const BusinessModelDetails: React.FC<RouteComponentProps> = (props) => {
  const { data, postData } = useActivation();
  const { onboarding_card_details: onboardingCardDetails, onboarding_milestone } = data;
  const [isBlurCalled, setIsBlurCalled] = useState(false);
  const [hasBusinessModel, setHasBusinessModel] = useState(
    onboardingCardDetails.business_subcategory.value === 'others',
  );
  const isBlackListed = data.activation_flow === 'blacklist';

  const handleSubmit = (updatedDetails) => {
    const reqData = getRequestData(onboardingCardDetails, updatedDetails);
    if (Object.keys(reqData).length) {
      postData(reqData);
    }
  };
  const handleBlur = (e, formikProps) => {
    formikProps.handleBlur(e);
    setIsBlurCalled(true);
  };
  const handleStartActivation = () => {
    postData({
      onboarding_milestone: 'activation_flow',
    })
      .then((res) => {
        if (res.onboarding_milestone === 'activation_flow') {
          props.history.push('/onboarding/steps');
        }
      })
      .catch((e) => {
        console.log('error with the API', e);
      });
  };
  if (!onboarding_milestone) {
    return (
      <Formik
        initialValues={{
          business_type: onboardingCardDetails.business_type.value,
          business_subcategory: onboardingCardDetails.business_subcategory.value,
          business_model: onboardingCardDetails.business_model.value,
        }}
        validationSchema={() => {
          return Yup.lazy((_values: BusinessModelDetailsI | undefined) => {
            return Yup.object().shape({
              business_type: Yup.string().trim().required(),
              business_subcategory: Yup.string().trim().required(),
              business_model: Yup.lazy(() => {
                if (_values && _values.business_subcategory === 'others') {
                  return Yup.string()
                    .trim()
                    .required('Business model is a required field')
                    .nullable();
                }
                return Yup.string().nullable();
              }),
            });
          });
        }}
        onSubmit={() => console.log('onSubmit')}
      >
        {(formikProps) => {
          let isFormValid = false;
          if (formikProps.isValid && !isBlackListed) {
            isFormValid = true;
          }
          return (
            <form
              onChange={formikProps.handleChange}
              onBlur={(e) => {
                handleBlur(e, formikProps);
              }}
            >
              <Field>
                <BusinessType
                  onboardingMilestone={onboarding_milestone}
                  value={formikProps.values.business_type}
                  errorText={formikProps.touched.business_type && formikProps.errors.business_type}
                  onChange={(value) => {
                    formikProps.setFieldTouched('business_type');
                    formikProps.setFieldValue('business_type', value);
                    setIsBlurCalled(true);
                  }}
                />
              </Field>
              <Field last={!hasBusinessModel}>
                <BusinessCategory
                  value={formikProps.values.business_subcategory}
                  errorText={
                    formikProps.touched.business_subcategory &&
                    formikProps.errors.business_subcategory
                  }
                  onChange={(value) => {
                    formikProps.setFieldTouched('business_subcategory');
                    formikProps.setFieldValue('business_subcategory', value);
                    setIsBlurCalled(true);
                    setHasBusinessModel(value === 'others');
                  }}
                />
              </Field>
              <Field visible={hasBusinessModel} last>
                <TextInput
                  name="business_model"
                  label="Business Model"
                  width="auto"
                  value={formikProps.values.business_model}
                  errorText={
                    formikProps.touched.business_model && formikProps.errors.business_model
                  }
                  helpText="Tell us a bit about your business model"
                />
              </Field>
              {isBlackListed ? (
                <Space margin={[2, 0, 0, 0]}>
                  <Text color="red.900" size="small">
                    We do not have the support for your business category selected as of now.
                  </Text>
                </Space>
              ) : null}
              <Space margin={[2.5, 0, 0, 0]}>
                <View>
                  <Button block onClick={handleStartActivation} disabled={!isFormValid}>
                    Start Activation
                  </Button>
                </View>
              </Space>
              <GetTouchedFields
                handleSubmit={handleSubmit}
                isBlurCalled={isBlurCalled}
                setIsBlurCalled={setIsBlurCalled}
              />
            </form>
          );
        }}
      </Formik>
    );
  }

  return null;
};

export default withRouter(BusinessModelDetails);
