import React, { useEffect, useState } from 'react';
import { ArrowRightIcon, Button, TextArea, TextInput } from '@razorpay/blade/components';
import { useFormik } from 'formik';
import isEmpty from 'lodash/isEmpty';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties, isMobileAndTablet } from 'common/utils/rzp-utils';
import { merchantFetch } from 'merchant/utils/ajax';
import { MobileHeader } from 'merchant/views/PartnerDashboard/Home/Components/PurePlatformSwitch/ApplicationFlow/Components/Header';
import {
  ApplicationFormProps,
  PARTNER_SWITCH_PRIVACY_POLICY,
  PARTNER_SWITCH_TERMS_AND_CONDITIONS,
  STEPS,
  validationSchema,
} from 'merchant/views/PartnerDashboard/Home/Components/PurePlatformSwitch/Constants';
import {
  ApplicationFooter,
  ApplicationFormContent,
  ApplicationFormImg,
  ApplicationFormWrapper,
  ServiceProvidedDescription,
  ServiceProvidedHeading,
} from 'merchant/views/PartnerDashboard/Home/Components/PurePlatformSwitch/Styled';
import { getExperimentsForTracking } from 'merchant/views/PartnerDashboard/Home/Components/utils';
import { showNotification } from 'merchant_common/reducers/notifications';

const getInitialState = () => {
  return {
    phoneNumber: '',
    websiteURL: '',
    otherInfo: '',
  };
};

const ApplicationForm = ({
  setStep,
  setIsOpen,
  trackingExperiments,
  user,
  showNotification,
}: ApplicationFormProps): JSX.Element => {
  const [isLoading, setIsLoading] = useState(false);

  const nextClick = (values) => {
    analyticsTrack({
      objectName: 'Migrate To PurePlatform Details Next',
      actionName: 'Clicked',
      screen: 'Details Screen',
      properties: {
        location: 'partner home',
        ...trackingExperiments,
        ...getCommonAnalyticsProperties(user),
        ...getExperimentsForTracking(user),
      },
    });
    const payload = {
      phone_no: values.phoneNumber,
      website_url: values.websiteURL,
      other_info: values.otherInfo,
      terms: {
        consent: true,
        url: PARTNER_SWITCH_TERMS_AND_CONDITIONS,
      },
    };

    setIsLoading(true);
    merchantFetch({
      url: 'partner/request_migration',
      method: 'POST',
      data: payload,
      mode: 'live',
    })
      .then((res) => {
        if (res.data.success) {
          setStep(STEPS.APPLICATION_RECEIVED);
        } else {
          showNotification({
            type: 'error',
            message: 'Something Went Wrong',
          });
        }
      })
      .catch((err) => {
        showNotification({
          type: 'error',
          message: err.errors,
        });
      })
      .finally(() => {
        setIsLoading(false);
      });
  };
  const formik = useFormik({
    initialValues: getInitialState(),
    enableReinitialize: true,
    validationSchema,
    validateOnChange: true,
    onSubmit: (values) => {
      nextClick(values);
    },
  });

  useEffect(() => {
    analyticsTrack({
      objectName: 'Migrate To PurePlatform Details Screen',
      actionName: 'Loaded',
      screen: 'Details Screen',
      properties: {
        location: 'partner home',
        ...trackingExperiments,
        ...getCommonAnalyticsProperties(user),
        ...getExperimentsForTracking(user),
      },
    });
    formik.setFieldValue('phoneNumber', user?.user?.contact_mobile);
  }, []);

  const onFormChange = (name, value) => {
    if (isEmpty(formik.touched)) {
      analyticsTrack({
        objectName: 'Migrate To PurePlatform Details',
        actionName: 'Entered',
        screen: 'Details Screen',
        properties: {
          location: 'partner home',
          field: name,
          ...trackingExperiments,
          ...getCommonAnalyticsProperties(user),
          ...getExperimentsForTracking(user),
        },
      });
    }
    formik.setFieldTouched(name);
    formik.setFieldValue(name, value);
  };

  const trackPrivacyPolicy = () => {
    analyticsTrack({
      objectName: 'Privacy Policy',
      actionName: 'Clicked',
      screen: 'Details Screen',
      properties: {
        location: 'partner home',
        ...trackingExperiments,
        ...getCommonAnalyticsProperties(user),
        ...getExperimentsForTracking(user),
      },
    });
  };

  const trackTermsOfUse = () => {
    analyticsTrack({
      objectName: 'Terms of Use',
      actionName: 'Clicked',
      screen: 'Details Screen',
      properties: {
        location: 'partner home',
        ...trackingExperiments,
        ...getCommonAnalyticsProperties(user),
        ...getExperimentsForTracking(user),
      },
    });
  };

  const defaultPhoneNumber = user?.user?.contact_mobile;
  let isButtonDisabled = false;
  if (defaultPhoneNumber) isButtonDisabled = !isEmpty(formik.errors) || !formik.touched.websiteURL;
  else
    isButtonDisabled =
      !isEmpty(formik.errors) || !formik.touched.phoneNumber || !formik.touched.websiteURL;

  const isMobileView = isMobileAndTablet();

  return (
    <div>
      <ServiceProvidedHeading>
        {isMobileView ? (
          <MobileHeader
            title="Fill in your details here"
            setIsOpen={setIsOpen}
            step={STEPS.APPLICATION_FORM}
            trackingExperiments={trackingExperiments}
          />
        ) : (
          'Fill in your details here'
        )}
      </ServiceProvidedHeading>
      <ServiceProvidedDescription>
        By clicking next I agree to submit my partner type switch request and accept the new&nbsp;
        <a href={PARTNER_SWITCH_PRIVACY_POLICY} target="__blank" onClick={trackPrivacyPolicy}>
          privacy policy
        </a>
        &nbsp;and&nbsp;
        <a href={PARTNER_SWITCH_TERMS_AND_CONDITIONS} target="__blank" onClick={trackTermsOfUse}>
          terms of use
        </a>
        .
      </ServiceProvidedDescription>

      <ApplicationFormContent>
        <ApplicationFormWrapper>
          <form onSubmit={formik.handleSubmit}>
            <TextInput
              autoFocus
              label="Phone Number"
              name="phoneNumber"
              placeholder="Type here"
              value={defaultPhoneNumber || formik.values.phoneNumber}
              labelPosition="left"
              necessityIndicator="required"
              maxCharacters={10}
              type="telephone"
              marginBottom="20px"
              marginTop="36px"
              onChange={({ name, value }) => {
                onFormChange(name, value);
              }}
              validationState={
                formik.touched.phoneNumber && formik.errors.phoneNumber ? 'error' : 'none'
              }
              errorText={formik.errors.phoneNumber}
              isDisabled={defaultPhoneNumber}
            />

            <TextInput
              label="Your website URL"
              name="websiteURL"
              placeholder="Type here"
              value={formik.values.websiteURL}
              labelPosition="left"
              necessityIndicator="required"
              type="url"
              marginBottom="20px"
              onChange={({ name, value }) => {
                onFormChange(name, value);
              }}
              validationState={
                formik.touched.websiteURL && formik.errors.websiteURL ? 'error' : 'none'
              }
              errorText={formik.errors.websiteURL}
            />

            <TextArea
              label="Share more about your Business type"
              name="otherInfo"
              placeholder="Start typing here"
              helpText="This could be social media link or anything else you want us to know"
              labelPosition="left"
              necessityIndicator="optional"
              numberOfLines={2}
              value={formik.values.otherInfo}
              onChange={({ name, value }) => {
                onFormChange(name, value);
              }}
              validationState={formik.errors.otherInfo ? 'error' : 'none'}
            />
          </form>
        </ApplicationFormWrapper>
        <ApplicationFormImg />
      </ApplicationFormContent>

      <ApplicationFooter>
        <div className="btn-wrap">
          {isMobileView ? (
            <Button
              isFullWidth
              onClick={() => formik.handleSubmit()}
              isDisabled={isButtonDisabled}
              isLoading={isLoading}
              icon={ArrowRightIcon}
              iconPosition="right"
            >
              Next
            </Button>
          ) : (
            <Button
              isFullWidth
              onClick={() => formik.handleSubmit()}
              isDisabled={isButtonDisabled}
              isLoading={isLoading}
            >
              Next
            </Button>
          )}
        </div>
      </ApplicationFooter>
    </div>
  );
};

export default connect(null, (dispatch) => bindActionCreators({ showNotification }, dispatch))(
  ApplicationForm,
);
