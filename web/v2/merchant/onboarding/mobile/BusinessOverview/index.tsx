import React, { useState } from 'react';
import styled from 'styled-components';
import { Formik } from 'formik';
import * as Yup from 'yup';
import View from '@razorpay/blade/src/atoms/View';
import Space from '@razorpay/blade/src/atoms/Space';
import TextInput from '@razorpay/blade/src/atoms/TextInput';
import Text from '@razorpay/blade/src/atoms/Text';
import Radio from '@razorpay/blade/src/atoms/Radio';
import HelpIcon from '@razorpay/blade/src/atoms/Icon';
import TextArea from '@razorpay/blade/src/atoms/TextArea';
import { FormSection, Field, GetTouchedFields } from '../Form';
import { useActivationFormState, isTabComplete } from '../context/store';
import useActivation, { getRequestData } from '../hooks/useActivation';
import BusinessType from '../Fields/BusinessType';
import BusinessCategory from '../Fields/BusinessCategory';
import BusinessAOV from '../Fields/BusinessAOV';
import useBusinessCategory from '../hooks/useBusinessCategory';
import { autoPrefixUrls, hasSelectedBlacklistCategory } from '../services/utils';
import { analyticsTrack, getCommonSegmentProperties } from '../../../../services/tracking/segment';
import { useApp } from 'v2/context/App';

interface BusinessOverviewProps {
  isFormLocked?: boolean;
}

const BusinessOverview: React.FC<BusinessOverviewProps> = ({ isFormLocked }) => {
  const { data, postData } = useActivation();
  const { user } = useApp();
  const [status, businessCategoriesData] = useBusinessCategory('');
  const businessOverview = data.business_overview;
  const setBusinessOverviewCompleted = useActivationFormState(
    (state) => state.setBusinessOverviewCompleted,
  );
  const hasWebsite = useActivationFormState((state) => state.has_website);
  const setHasWebsite = useActivationFormState((state) => state.setHasWebsite);
  const [isBlurCalled, setIsBlurCalled] = useState(false);
  const [isUnregistered, setIsUnregistered] = useState(false);
  const [websiteOption, setWebiteOption] = useState('1');
  const setIsOpen = useActivationFormState((state) => state.setIsFAQOpen);
  const setFAQSection = useActivationFormState((state) => state.setFAQSection);

  const handleBlur = (e, formikProps) => {
    formikProps.handleBlur(e);
    setIsBlurCalled(true);
  };

  const Container = styled(View)`
    position: relative;
  `;
  const IconContainer = styled.span`
    position: absolute;
    right: 12px;
    top: 4px;
  `;

  const handleSubmit = (updatedDetails) => {
    if (updatedDetails.business_website?.value) {
      const prefixUrl = autoPrefixUrls(updatedDetails.business_website.value);
      updatedDetails = {
        ...updatedDetails,
        business_website: {
          value: prefixUrl,
          error: updatedDetails.business_website.error,
        },
      };
    }

    if (updatedDetails.merchant_avg_order_value) {
      let merchant_avg_order_value;
      if (updatedDetails.merchant_avg_order_value.value === 'More than ₹ 1,00,000') {
        merchant_avg_order_value = {
          min_aov: 100000,
          max_aov: 0,
        };
      } else {
        const value = updatedDetails.merchant_avg_order_value.value.split('-');
        const min = value[0];
        const max = value[1];
        merchant_avg_order_value = {
          value: {
            min_aov: min,
            max_aov: max,
          },
        };
      }
      updatedDetails = { ...updatedDetails, merchant_avg_order_value };
    }

    const isComplete = isTabComplete(
      { ...data, business_overview: { ...businessOverview, ...updatedDetails }, hasWebsite },
      'business_overview',
    );
    setBusinessOverviewCompleted(isComplete);
    const reqData = getRequestData(businessOverview, updatedDetails);
    if (Object.keys(reqData).length) {
      postData(reqData);
    }
  };

  const showUnregisteredText = () => {
    return isUnregistered ? (
      <Text size="xxsmall" color="shade.960" align="justify">
        Unregistered business type is for freelancers or small businesses who have not yet
        registered as a company. Don't choose this option if your business is already registered.
        Business type cannot be changed once submitted.
      </Text>
    ) : null;
  };

  return (
    <Formik
      initialValues={{
        business_type: businessOverview.business_type.value,
        business_dba: businessOverview.business_dba.value,
        business_website: businessOverview.business_website.value,
        business_subcategory: businessOverview.business_subcategory.value,
        merchant_avg_order_value: businessOverview.merchant_avg_order_value.value,
        business_model: businessOverview.business_model.value,
      }}
      initialErrors={{
        business_type: businessOverview.business_type.error,
        business_dba: businessOverview.business_dba.error,
        business_website: businessOverview.business_website.error,
        business_subcategory: businessOverview.business_subcategory.error,
        merchant_avg_order_value: businessOverview.merchant_avg_order_value.error,
        business_model: businessOverview.business_model.error,
      }}
      validationSchema={() => {
        const _schema = Yup.object().shape({
          business_type: Yup.string()
            .nullable()
            .required('Business Type is a required field')
            .nullable(),
          business_dba: Yup.string()
            .nullable()
            .required('Billing Label is a required field')
            .nullable(),
          business_category: Yup.string()
            .nullable()
            .required('Please select your business category')
            .nullable(),
          business_website: Yup.lazy(() => {
            if (hasWebsite) {
              return Yup.string().required('Please provide website').nullable();
            }
            return Yup.string().nullable();
          }),
        });
        return _schema;
      }}
      validateOnMount={false}
      onSubmit={() => {
        console.log('onSubmit');
      }}
      enableReinitialize
    >
      {(formikProps) => {
        const isBlackListed =
          status === 'success' &&
          hasSelectedBlacklistCategory(formikProps.values, businessCategoriesData);

        return (
          <form
            onSubmit={formikProps.handleSubmit}
            onChange={formikProps.handleChange}
            onBlur={(e) => handleBlur(e, formikProps)}
          >
            <FormSection title="About Your Business">
              <Field>
                <BusinessType
                  onboardingMilestone={data.onboarding_milestone}
                  value={formikProps.values.business_type}
                  errorText={formikProps.touched.business_type && formikProps.errors.business_type}
                  onChange={(value) => {
                    if (Number(value) === 11 || Number(value) === 2) setIsUnregistered(true);
                    else setIsUnregistered(false);
                    formikProps.setFieldTouched('business_type');
                    formikProps.setFieldValue('business_type', value);
                    setIsBlurCalled(true);
                    analyticsTrack({
                      objectName: 'SignUp',
                      actionName: 'Business type success',
                      screen: 'home page',
                      properties: {
                        userId: user.id,
                        ...getCommonSegmentProperties(),
                      },
                    });
                  }}
                  disabled={isFormLocked}
                />
                {showUnregisteredText()}
              </Field>
              <Field>
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
                    analyticsTrack({
                      objectName: 'SignUp',
                      actionName: 'Business category success',
                      screen: 'home page',
                      properties: {
                        userId: user.id,
                        ...getCommonSegmentProperties(),
                      },
                    });
                  }}
                  disabled={isFormLocked}
                />
                {isBlackListed ? (
                  <Space margin={[1, 0, 0, 0]}>
                    <Text color="red.900" size="small">
                      We do not have the support for your business category selected as of now.
                    </Text>
                  </Space>
                ) : null}
              </Field>

              <Field>
                <Container>
                  <TextInput
                    width="auto"
                    name="business_dba"
                    label="Billing Label"
                    helpText="Something that your customers are familiar with"
                    value={formikProps.values.business_dba}
                    errorText={formikProps.touched.business_dba && formikProps.errors.business_dba}
                    disabled={isFormLocked}
                    onChange={(value) => {
                      formikProps.setFieldValue('business_dba', value);
                    }}
                    onBlur={() => {
                      analyticsTrack({
                        objectName: 'SignUp',
                        actionName: 'Business label success',
                        screen: 'home page',
                        properties: {
                          userId: user.id,
                          ...getCommonSegmentProperties(),
                        },
                      });
                    }}
                  />
                  <IconContainer
                    onClick={() => {
                      if (isFormLocked) {
                        return;
                      }
                      setFAQSection('Q1');
                      setIsOpen(true);
                    }}
                  >
                    <HelpIcon
                      name="helpCircle"
                      size="small"
                      fill={isFormLocked ? 'shade.930' : 'primary.800'}
                    />
                  </IconContainer>
                </Container>
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
                      disabled={isFormLocked}
                      onBlur={() => {
                        analyticsTrack({
                          objectName: 'SignUp',
                          actionName: 'Business model success',
                          screen: 'home page',
                          properties: {
                            userId: user.id,
                            ...getCommonSegmentProperties(),
                          },
                        });
                      }}
                      errorText={
                        formikProps.touched.business_model && formikProps.errors.business_model
                      }
                      helpText="Tell us about the products you sell, your customers and the channels you primarily use for business ( Website, offline retail, etc )"
                    />
                  </View>
                </Space>
              </Field>
              <Field last>
                <Space margin={[3.7, 0, 0, 0]}>
                  <View>
                    <BusinessAOV
                      value={formikProps.values.merchant_avg_order_value}
                      errorText={
                        formikProps.touched.merchant_avg_order_value &&
                        formikProps.errors.merchant_avg_order_value
                      }
                      disabled={isFormLocked}
                      onChange={(value) => {
                        formikProps.setFieldTouched('merchant_avg_order_value');
                        formikProps.setFieldValue('merchant_avg_order_value', value);
                        setIsBlurCalled(true);
                        analyticsTrack({
                          objectName: 'SignUp',
                          actionName: 'Business Aov success',
                          screen: 'home page',
                          properties: {
                            userId: user.id,
                            ...getCommonSegmentProperties(),
                          },
                        });
                      }}
                    />
                  </View>
                </Space>
              </Field>
            </FormSection>

            <FormSection title="Website Details" last disabled={isFormLocked}>
              <Field last>
                <Radio
                  defaultValue={hasWebsite ? '0' : '1'}
                  size="medium"
                  onChange={(val) => {
                    const _hasWebsite = val == '0';
                    setWebiteOption(val);
                    setHasWebsite(_hasWebsite);
                    if (!_hasWebsite) {
                      formikProps.setFieldTouched('business_website');
                      formikProps.setFieldValue('business_website', '');
                      analyticsTrack({
                        objectName: 'SignUp',
                        actionName: 'no live website initiated',
                        screen: 'home page',
                        properties: {
                          userId: user.id,
                          ...getCommonSegmentProperties(),
                        },
                      });
                    }
                    setIsBlurCalled(true);
                    if (hasWebsite) {
                      analyticsTrack({
                        objectName: 'SignUp',
                        actionName: 'live website initiated',
                        screen: 'home page',
                        properties: {
                          userId: user.id,
                          ...getCommonSegmentProperties(),
                        },
                      });
                    }
                  }}
                >
                  <Radio.Option value="0" title="I have a live website/app" />
                  {hasWebsite ? (
                    <Space margin={[3.75, 0, 0, 3.5]}>
                      <Container>
                        <TextInput
                          width="auto"
                          name="business_website"
                          label="Website/App URL"
                          helpText="Click the help icon to view the mandatory sections required in your website/app for quick verification"
                          value={formikProps.values.business_website}
                          errorText={
                            formikProps.touched.business_website &&
                            formikProps.errors.business_website
                          }
                          disabled={isFormLocked}
                        />
                        <IconContainer
                          onClick={() => {
                            if (isFormLocked) {
                              return;
                            }
                            setFAQSection('Q2');
                            setIsOpen(true);
                          }}
                        >
                          <HelpIcon
                            name="helpCircle"
                            size="small"
                            fill={isFormLocked ? 'shade.930' : 'primary.800'}
                          />
                        </IconContainer>
                      </Container>
                    </Space>
                  ) : null}
                  <Space margin={[1, 0]}>
                    <View>
                      <Radio.Option value="1" title="I don't have a website/app" />
                      {websiteOption === '1' && (
                        <Space margin={[0, 0, 0, 3.5]}>
                          <Text color="shade.950" size="xsmall">
                            Use payment links, invoices and many otherproducts from our suite to
                            accept payments
                          </Text>
                        </Space>
                      )}
                    </View>
                  </Space>
                </Radio>
              </Field>
            </FormSection>
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
};

export default BusinessOverview;
