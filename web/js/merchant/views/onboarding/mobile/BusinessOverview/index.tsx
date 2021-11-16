import React, { useState } from 'react';
import styled from 'styled-components';
import { Formik } from 'formik';
import * as Yup from 'yup';
import View from '@razorpay/blade-old/src/atoms/View';
import Space from '@razorpay/blade-old/src/atoms/Space';
import TextInput from '@razorpay/blade-old/src/atoms/TextInput';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Radio from '@razorpay/blade-old/src/atoms/Radio';
import Checkbox from '@razorpay/blade-old/src/atoms/Checkbox';
import Icon from '@razorpay/blade-old/src/atoms/Icon';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import TextArea from '@razorpay/blade-old/src/atoms/TextArea';
import { FormSection, Field, GetTouchedFields } from '../Form';
import { useActivationFormState, isTabComplete } from '../context/store';
import useActivation, { getRequestData } from '../hooks/useActivation';
import BusinessType from '../Fields/BusinessType';
import BusinessCategory from '../Fields/BusinessCategory';
import BusinessAOV from '../Fields/BusinessAOV';
import useBusinessCategory from '../hooks/useBusinessCategory';
import { autoPrefixUrls, hasSelectedBlacklistCategory } from '../services/utils';
import { analyticsTrack } from 'common/services/tracking/segment';
import { useApp } from 'common/context/App';
import usePartnerActivation from '../hooks/usePartnerActivation';

interface BusinessOverviewProps {
  isFormLocked?: boolean;
}

const BusinessOverview: React.FC<BusinessOverviewProps> = ({ isFormLocked }) => {
  const { data, postData } = useActivation();
  const { user, experiments } = useApp();
  const [status, businessCategoriesData] = useBusinessCategory('');
  const businessOverview = data.business_overview;
  const setBusinessOverviewCompleted = useActivationFormState(
    (state) => state.setBusinessOverviewCompleted,
  );
  const hasWebsiteOrApp = useActivationFormState((state) => state.has_website_or_app);
  const setHasWebsiteOrApp = useActivationFormState((state) => state.setHasWebsiteOrApp);
  const hasWebsite = useActivationFormState((state) => state.has_website);
  const setHasWebsite = useActivationFormState((state) => state.setHasWebsite);
  const hasApp = useActivationFormState((state) => state.has_app);
  const setHasApp = useActivationFormState((state) => state.setHasApp);
  const playStoreURL = data.playstore_url;
  const [isBlurCalled, setIsBlurCalled] = useState(false);
  const [isUnregistered, setIsUnregistered] = useState(false);
  const [websiteOption, setWebiteOption] = useState('1');
  const setIsOpen = useActivationFormState((state) => state.setIsFAQOpen);
  const setFAQSection = useActivationFormState((state) => state.setFAQSection);
  const { getFieldStatus } = usePartnerActivation();

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

  const WithoutWebsiteListText = styled.ul`
    padding: 0;
  `;
  const List = styled.li`
    margin-bottom: 12px;
    padding: ${({ isLast }) => (isLast ? '12px' : 'initial')};
    list-style: ${({ isLast }) => (isLast ? 'none' : 'initial')};
    background: ${({ isLast }) => (isLast ? '#edf0f5' : 'initial')};
    border-radius: ${({ isLast }) => (isLast ? '4px' : 'initial')};
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
      if (updatedDetails.merchant_avg_order_value.value === '100000-0') {
        merchant_avg_order_value = {
          value: {
            min_aov: 100000,
            max_aov: 0,
          },
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
      {
        ...data,
        business_overview: { ...businessOverview, ...updatedDetails },
        hasWebsiteOrApp,
        hasWebsite,
        hasApp,
      },
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
        playstore_url: playStoreURL,
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
            .min(3, 'Please enter billing label with at least 3 characters')
            .required('Billing Label is a required field')
            .nullable(),
          business_category: Yup.string()
            .nullable()
            .required('Please select your business category')
            .nullable(),
          business_website: Yup.lazy(() => {
            if (hasWebsiteOrApp) {
              return Yup.string()
                .matches(
                  /^(https?:\/\/)?[\w.-]+(?:\.[\w\\.-]+)+[\w\-\\._~:/?#[\]@!\\$&'\\(\\)\\*\\+,;=.]+$/,
                  {
                    message: 'Please enter a valid url',
                    excludeEmptyString: true,
                  },
                )
                .required('Please provide website')
                .nullable();
            }
            return Yup.string().nullable();
          }),
          playstore_url: Yup.lazy(() => {
            if (hasWebsiteOrApp) {
              return Yup.string().required('Please provide app store link').nullable();
            }
            return Yup.string().nullable();
          }),
          business_model: Yup.string().min(
            200,
            'Business Description should be at least 200 Characters',
          ),
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

        if (isBlackListed) {
          analyticsTrack({
            objectName: 'SignUp',
            actionName: 'business overview',
            screen: 'home page',
            eventAction: 'failed',
            user,
          });
        }
        return (
          <form
            onSubmit={formikProps.handleSubmit}
            onChange={formikProps.handleChange}
            onBlur={(e) => handleBlur(e, formikProps)}
          >
            <FormSection title="About Your Business">
              <Field>
                <BusinessType
                  onboardingMilestone={data.activation_form_milestone}
                  value={formikProps.values.business_type}
                  errorText={formikProps.touched.business_type && formikProps.errors.business_type}
                  onChange={(value) => {
                    if (Number(value) === 11 || Number(value) === 2) setIsUnregistered(true);
                    else setIsUnregistered(false);
                    formikProps.setFieldTouched('business_type');
                    formikProps.setFieldValue('business_type', value);
                    setIsBlurCalled(true);
                  }}
                  disabled={isFormLocked || getFieldStatus('business_type').isDisabled}
                />
                {getFieldStatus('business_type').description || showUnregisteredText()}
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
                      actionName: 'Business category',
                      screen: 'home page',
                      eventAction: 'success',
                      user,
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
                    helpText="Your brand name that your customers are familiar with"
                    value={formikProps.values.business_dba}
                    errorText={formikProps.touched.business_dba && formikProps.errors.business_dba}
                    disabled={isFormLocked}
                    onChange={(value) => {
                      formikProps.setFieldValue('business_dba', value);
                    }}
                  />
                  <IconContainer
                    onClick={() => {
                      if (isFormLocked) {
                        return;
                      }
                      analyticsTrack({
                        objectName: 'SignUp',
                        actionName: 'faq',
                        screen: 'home page',
                        user,
                        eventAction: 'initiated',
                        properties: {
                          clickSource: 'billing label',
                        },
                      });
                      setFAQSection('Q1');
                      setIsOpen(true);
                    }}
                  >
                    <Icon
                      name="helpCircle"
                      size="small"
                      fill={isFormLocked ? 'shade.930' : 'primary.800'}
                    />
                  </IconContainer>
                </Container>
              </Field>
              <Field last={experiments.isLiteOnboarding}>
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
                      errorText={
                        formikProps.touched.business_model && formikProps.errors.business_model
                      }
                      helpText="Tell us about the products you sell, your customers and the channels you primarily use for business ( Website, offline retail, etc) with minimum 200 characters"
                      maxLength={255}
                    />
                  </View>
                </Space>
              </Field>
              <Field visible={!experiments.isLiteOnboarding} last={!experiments.isLiteOnboarding}>
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
                      }}
                    />
                  </View>
                </Space>
              </Field>
            </FormSection>

            <FormSection title="Website Details" last disabled={isFormLocked}>
              <Field last>
                <Radio
                  defaultValue={hasWebsiteOrApp ? '0' : '1'}
                  size="medium"
                  onChange={(val) => {
                    const _hasWebsite = val == '0';
                    setWebiteOption(val);
                    setHasWebsiteOrApp(_hasWebsite);
                    if (!_hasWebsite) {
                      formikProps.setFieldTouched('business_website');
                      formikProps.setFieldTouched('playstore_url');
                      formikProps.setFieldValue('business_website', '');
                      formikProps.setFieldValue('playstore_url', '');
                      setHasWebsite(false);
                      setHasApp(false);
                    }
                    setIsBlurCalled(true);
                  }}
                >
                  <Radio.Option value="0" title="I have a live website/app" />
                  {hasWebsiteOrApp &&
                    !formikProps.values.playstore_url &&
                    !formikProps.values.business_website && (
                      <Space margin={[-1, 0, 0, 3.5]}>
                        <Text size="xsmall" color="negative.900">
                          Please choose at least one option{' '}
                        </Text>
                      </Space>
                    )}
                  {hasWebsiteOrApp ? (
                    <View>
                      <Space margin={[1, 0, 0, 3]}>
                        <Container>
                          <Checkbox
                            title="Accept payments on website"
                            size="medium"
                            onChange={(checked) => {
                              if (checked) {
                                setHasWebsite(true);
                              } else {
                                formikProps.setFieldTouched('business_website');
                                formikProps.setFieldValue('business_website', '');
                                setHasWebsite(false);
                              }
                              setIsBlurCalled(true);
                            }}
                            defaultChecked={hasWebsite}
                          />
                        </Container>
                      </Space>
                      {hasWebsite && (
                        <Space margin={[2, 0, 0, 3.5]}>
                          <Container>
                            <TextInput
                              width="auto"
                              name="business_website"
                              label="Website URL"
                              helpText="Click the help icon to view the mandatory sections required in your website for quick verification"
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
                                analyticsTrack({
                                  objectName: 'SignUp',
                                  actionName: 'faq',
                                  screen: 'home page',
                                  user,
                                  eventAction: 'initiated',
                                  properties: {
                                    clickSource: 'business website',
                                  },
                                });
                                setFAQSection('Q2');
                                setIsOpen(true);
                              }}
                            >
                              <Icon
                                name="helpCircle"
                                size="small"
                                fill={isFormLocked ? 'shade.930' : 'primary.800'}
                              />
                            </IconContainer>
                          </Container>
                        </Space>
                      )}

                      <Space margin={[2, 0, 0, 3]}>
                        <Container>
                          <Checkbox
                            title="Accept payments on app"
                            size="medium"
                            onChange={(checked) => {
                              if (checked) {
                                setHasApp(true);
                              } else {
                                formikProps.setFieldTouched('playstore_url');
                                formikProps.setFieldValue('playstore_url', '');
                                setHasApp(false);
                              }
                              setIsBlurCalled(true);
                            }}
                            defaultChecked={hasApp}
                          />
                        </Container>
                      </Space>
                      {hasApp && (
                        <Space margin={[2, 0, 0, 3.5]}>
                          <Container>
                            <TextInput
                              width="auto"
                              name="playstore_url"
                              label="App URL"
                              helpText="Click the help icon to view the mandatory sections required in your app for quick verification"
                              value={formikProps.values.playstore_url}
                              errorText={
                                formikProps.touched.playstore_url &&
                                formikProps.errors.playstore_url
                              }
                              disabled={isFormLocked}
                            />
                            <IconContainer
                              onClick={() => {
                                if (isFormLocked) {
                                  return;
                                }
                                analyticsTrack({
                                  objectName: 'SignUp',
                                  actionName: 'faq',
                                  screen: 'home page',
                                  user,
                                  eventAction: 'initiated',
                                  properties: {
                                    clickSource: 'business app',
                                  },
                                });
                                setFAQSection('Q2');
                                setIsOpen(true);
                              }}
                            >
                              <Icon
                                name="helpCircle"
                                size="small"
                                fill={isFormLocked ? 'shade.930' : 'primary.800'}
                              />
                            </IconContainer>
                          </Container>
                        </Space>
                      )}
                    </View>
                  ) : null}
                  <Space margin={[1, 0]}>
                    <View>
                      <Radio.Option value="1" title="I don't have a website/app" />
                      {websiteOption === '1' && (
                        <Space margin={[0, 0, 0, 3.5]}>
                          <Text color="shade.950" size="xsmall">
                            {experiments.canGenerateTnCPage ? (
                              <WithoutWebsiteListText>
                                <List>
                                  Receive payments from your customers in under 5 minutes using
                                  Razorpay’s payment pages and payment links
                                </List>
                                <List>
                                  You can submit your website/app anytime later if you wish to
                                  accept payments using it
                                </List>
                                <List isLast={true}>
                                  <Flex>
                                    <View>
                                      <Space margin={[0, 1, 0, 0]}>
                                        <View>
                                          <Icon name="info" fill="shade.800" size="small" />
                                        </View>
                                      </Space>
                                      <Text size="small" color="shade.970">
                                        As per RBI guidelines, terms and coditions are required to
                                        accept payments. We will help you generate one after KYC
                                        submission
                                      </Text>
                                    </View>
                                  </Flex>
                                </List>
                              </WithoutWebsiteListText>
                            ) : (
                              <>
                                Use payment links, invoices and many otherproducts from our suite to
                                accept payments
                              </>
                            )}
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
