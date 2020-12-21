import React, { useState } from 'react';
import { Formik } from 'formik';
import * as Yup from 'yup';
import TextInput from '@razorpay/blade/src/atoms/TextInput';
import Text from '@razorpay/blade/src/atoms/Text';
import Space from '@razorpay/blade/src/atoms/Space';
import View from '@razorpay/blade/src/atoms/View';
import Button from '@razorpay/blade/src/atoms/Button';
import Icon from '@razorpay/blade/src/atoms/Icon';
import { withRouter, RouteComponentProps } from 'react-router-dom';
import { Select, Option, GrpOption } from 'v2/components/Select';
import { Field, GetTouchedFields } from '../Form';
import useActivation, { getRequestData } from '../hooks/useActivation';
import useBusinessCategory from '../hooks/useBusinessCategory';
import { BusinessTypes } from '../Constants/OnboardingConstants';

//Todo: Need to use Lodash in dashboard codebase
function debounce(cb, time) {
  let timeout;
  return (...args) => {
    clearTimeout(timeout);
    timeout = setTimeout(cb, time, ...args);
  };
}
interface BusinessModelDetailsI {
  business_type: string;
  business_subcategory: string;
  business_model: string;
}

const BusinessModelDetails: React.FC<RouteComponentProps> = (props) => {
  const { data, postData } = useActivation();
  const { onboarding_card_details: onboardingCardDetails, onboarding_milestone } = data;
  const [isBlurCalled, setIsBlurCalled] = useState(false);
  const [inputValue, setInputValue] = useState('');
  const onInputChange = debounce(setInputValue, 200);
  const [businessCategoriesStatus, businessCategoriesData] = useBusinessCategory(inputValue);
  const [hasBusinessModel, setHasBusinessModel] = useState(
    onboardingCardDetails.business_subcategory.value === 'others',
  );

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
          return (
            <form
              onChange={formikProps.handleChange}
              onBlur={(e) => {
                handleBlur(e, formikProps);
              }}
            >
              <Field>
                <Select
                  label="Business Type"
                  errorText={formikProps.touched.business_type && formikProps.errors.business_type}
                  value={formikProps.values.business_type}
                  onChange={(value) => {
                    formikProps.setFieldTouched('business_type');
                    formikProps.setFieldValue('business_type', value);
                    setIsBlurCalled(true);
                  }}
                >
                  {Object.keys(BusinessTypes).map((business_type) => (
                    <Option
                      key={business_type}
                      value={business_type}
                      label={BusinessTypes[business_type]}
                    >
                      {BusinessTypes[business_type]}
                    </Option>
                  ))}
                </Select>
              </Field>
              <Field last={!hasBusinessModel}>
                <Select
                  label="Business Category"
                  searchable={true}
                  filterOptions={false}
                  errorText={
                    formikProps.touched.business_subcategory &&
                    formikProps.errors.business_subcategory
                  }
                  loading={businessCategoriesStatus === 'loading'}
                  value={formikProps.values.business_subcategory}
                  onInputChange={onInputChange}
                  onChange={(value) => {
                    formikProps.setFieldTouched('business_subcategory');
                    formikProps.setFieldValue('business_subcategory', value);
                    setIsBlurCalled(true);
                    setHasBusinessModel(value === 'others');
                  }}
                >
                  {businessCategoriesData
                    ? businessCategoriesData.map((item, index) => (
                        <GrpOption key={index} label={item.group_name}>
                          {item.matches.map((_item, _index) => (
                            <Option
                              key={_index}
                              value={_item.subcategory_value}
                              label={_item.subcategory_name}
                            >
                              <Text size="medium" color="shade.970">
                                {_item.subcategory_name}
                              </Text>
                              {_item.tags.length ? (
                                <Text size="small" color="shade.950">
                                  includes: {_item.tags.join(', ')}
                                </Text>
                              ) : null}
                            </Option>
                          ))}
                        </GrpOption>
                      ))
                    : null}
                  {businessCategoriesData &&
                  businessCategoriesData.length > 0 &&
                  hasBusinessModel ? (
                    <GrpOption key="others" label="Others">
                      <Option
                        key="1"
                        value="others"
                        label="I am unable to find my business category"
                      >
                        <Text size="small">I am unable to find my business category</Text>
                      </Option>
                    </GrpOption>
                  ) : null}
                  {businessCategoriesData && businessCategoriesData.length === 0 ? (
                    <>
                      <Option key="0" value="none" label="none" disabled={true}>
                        <Text align="center">
                          <Icon name="search" />
                        </Text>
                        <Text align="center" size="small">
                          No matching category found. Try another keyword or choose something from
                          the options above
                        </Text>
                      </Option>
                      <Option
                        key="1"
                        value="others"
                        label="I am unable to find my business category"
                      >
                        <Text size="small">I am unable to find my business category</Text>
                      </Option>
                    </>
                  ) : null}
                </Select>
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
              <Space margin={[2.5, 0, 0, 0]}>
                <View>
                  <Button block onClick={handleStartActivation} disabled={!formikProps.isValid}>
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
