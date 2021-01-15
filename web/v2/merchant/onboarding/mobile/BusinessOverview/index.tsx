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
import { FormSection, Field, GetTouchedFields } from '../Form';
import { useActivationFormState, isTabComplete } from '../context/store';
import useActivation, { getRequestData } from '../hooks/useActivation';
import BusinessType from '../Fields/BusinessType';
import BusinessCategory from '../Fields/BusinessCategory';
import { autoPrefixUrls } from '../services/utils';

const BusinessOverview: React.FC = () => {
  const { data, postData } = useActivation();
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
      }}
      initialErrors={{
        business_type: businessOverview.business_type.error,
        business_dba: businessOverview.business_dba.error,
        business_website: businessOverview.business_website.error,
        business_subcategory: businessOverview.business_subcategory.error,
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
      {(formikProps) => (
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
                }}
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
                disabled={true}
              />
            </Field>
            <Field last>
              <Container>
                <TextInput
                  width="auto"
                  name="business_dba"
                  label="Billing Label"
                  helpText="Something that your customers are familiar with"
                  value={formikProps.values.business_dba}
                  errorText={formikProps.touched.business_dba && formikProps.errors.business_dba}
                />
                <IconContainer
                  onClick={() => {
                    setFAQSection('Q1');
                    setIsOpen(true);
                  }}
                >
                  <HelpIcon name="helpCircle" size="small" fill="primary.800" />
                </IconContainer>
              </Container>
            </Field>
          </FormSection>

          <FormSection title="Website Details" last>
            <Field last>
              <Radio
                defaultValue={hasWebsite ? '0' : '1'}
                size="medium"
                onChange={(val) => {
                  const _hasWebsite = val === '0';
                  setWebiteOption(val);
                  setHasWebsite(_hasWebsite);
                  if (!_hasWebsite) {
                    formikProps.setFieldValue('business_website', '');
                  }
                  setIsBlurCalled(true);
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
                      />
                      <IconContainer
                        onClick={() => {
                          setFAQSection('Q2');
                          setIsOpen(true);
                        }}
                      >
                        <HelpIcon name="helpCircle" size="small" fill="primary.800" />
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
      )}
    </Formik>
  );
};

export default BusinessOverview;
