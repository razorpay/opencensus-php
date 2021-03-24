import React from 'react';
import { Formik } from 'formik';
import Space from '@razorpay/blade/src/atoms/Space';
import View from '@razorpay/blade/src/atoms/View';
import Button from '@razorpay/blade/src/atoms/Button';
import { withRouter, RouteComponentProps } from 'react-router-dom';
import useActivation from '../hooks/useActivation';
import useBusinessCategory from '../hooks/useBusinessCategory';
import { hasSelectedBlacklistCategory } from '../services/utils';
import { analyticsTrack, getCommonSegmentProperties } from '../../../../services/tracking/segment';
import { useApp } from 'v2/context/App';

interface BusinessModelDetailsI {
  business_type: string;
  business_subcategory: string;
  business_model: string;
}

const BusinessModelDetails: React.FC<RouteComponentProps> = (props) => {
  const { data } = useActivation();
  const { user } = useApp();
  const [status, businessCategoriesData] = useBusinessCategory('');
  const { onboarding_milestone } = data;
  // const [hasBusinessModel, setHasBusinessModel] = useState(
  //   onboardingCardDetails.business_subcategory.value === 'others',
  // );

  const handleStartActivation = () => {
    props.history.push('/onboarding/steps');
    // const body = {
    //   business_subcategory: formDetails.business_subcategory,
    //   business_type: formDetails.business_type,
    //   business_model: formDetails.business_model,
    // };
    analyticsTrack({
      objectName: 'SignUp',
      actionName: 'activation flow initiated',
      screen: 'home page',
      properties: {
        userId: user.id,
        ...getCommonSegmentProperties(),
      },
    });

    // postData(body)
    //   .then((res) => {
    //     if (res.onboarding_milestone === 'activation_flow') {
    //       props.history.push('/onboarding/steps');
    //     }
    //     analyticsTrack({
    //       objectName: 'SignUp',
    //       actionName: 'onboarding form activation form fill success',
    //       screen: 'home page',
    //       properties: {
    //          userId: user.id,
    //...getCommonSegmentProperties(),
    //       },
    //     });
    //   })
    //   .catch((e) => {
    //     console.log('error with the API', e);
    //     analyticsTrack({
    //       objectName: 'SignUp',
    //       actionName: 'onboarding form activation form fill  failed',
    //       screen: 'home page',
    //       properties: {
    //          userId: user.id,
    //...getCommonSegmentProperties(),
    //       },
    //     });
    //   });
  };
  if (!onboarding_milestone) {
    return (
      <Formik
        initialValues={
          {
            // business_type: onboardingCardDetails.business_type.value,
            // business_subcategory: onboardingCardDetails.business_subcategory.value,
            // business_model: onboardingCardDetails.business_model.value,
            // business_aov_range: '',
          }
        }
        validationSchema={() => {
          // return Yup.lazy((_values: BusinessModelDetailsI | undefined) => {
          //   return Yup.object().shape({
          //     business_type: Yup.string().trim().required(),
          //     business_subcategory: Yup.string().trim().required(),
          //     business_model: Yup.lazy(() => {
          //       if (_values && _values.business_subcategory === 'others') {
          //         return Yup.string()
          //           .trim()
          //           .required('Business model is a required field')
          //           .nullable();
          //       }
          //       return Yup.string().nullable();
          //     }),
          //     business_aov_range: Yup.string().trim().required(),
          //   });
          // });
        }}
        onSubmit={() => console.log('onSubmit')}
      >
        {(formikProps) => {
          const isBlackListed =
            status === 'success' &&
            hasSelectedBlacklistCategory(formikProps.values, businessCategoriesData);
          let isFormValid = false;
          if (formikProps.isValid && !isBlackListed) {
            isFormValid = true;
          }
          return (
            <form>
              {/* <Field>
                <BusinessType
                  onboardingMilestone={onboarding_milestone}
                  value={formikProps.values.business_type}
                  errorText={formikProps.touched.business_type && formikProps.errors.business_type}
                  onChange={(value) => {
                    formikProps.setFieldValue('business_type', value);
                  }}
                />
              </Field>
              <Field>
                <BusinessCategory
                  value={formikProps.values.business_subcategory}
                  errorText={
                    formikProps.touched.business_subcategory &&
                    formikProps.errors.business_subcategory
                  }
                  onChange={(value) => {
                    formikProps.setFieldValue('business_subcategory', value);
                    setHasBusinessModel(value === 'others');
                  }}
                />
                <Space margin={[0.3, 0, 0, 0]}>
                  <Text color="shade.950" size="xsmall">
                    Business category cannot be changed once submitted
                  </Text>
                </Space>
              </Field>
              <Field>
                <Space margin={[3.7, 0, 0, 0]}>
                  <View>
                    <TextArea
                      name="business_model"
                      label="Business Model"
                      placeholder="Enter text here"
                      width="auto"
                      value={formikProps.values.business_model}
                      onChange={(value) => {
                        formikProps.setFieldValue('business_model', value);
                      }}
                      errorText={
                        formikProps.touched.business_model && formikProps.errors.business_model
                      }
                      helpText="Tell us a bit about your business model"
                    />
                  </View>
                </Space>
              </Field>
              <Field last>
                <Space margin={[3.7, 0, 0, 0]}>
                  <View>
                    <BusinessAOV
                      value={formikProps.values.business_aov_range}
                      errorText={
                        formikProps.touched.business_aov_range &&
                        formikProps.errors.business_aov_range
                      }
                      onChange={(value) => {
                        formikProps.setFieldValue('business_aov_range', value);
                      }}
                    />
                  </View>
                </Space>
              </Field> 
              {isBlackListed ? (
                <Space margin={[2, 0, 0, 0]}>
                  <Text color="red.900" size="small">
                    We do not have the support for your business category selected as of now.
                  </Text>
                </Space>
              ) : null} */}
              <Space margin={[2.5, 0, 0, 0]}>
                <View>
                  <Button
                    block
                    size="large"
                    onClick={handleStartActivation}
                    disabled={!isFormValid}
                  >
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

export default withRouter(BusinessModelDetails);
