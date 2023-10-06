import React, { useEffect, useState } from 'react';
import { FormikValues, useFormik } from 'formik';
import { isEmpty } from 'lodash';
import { connect } from 'react-redux';
import { compose, bindActionCreators } from 'redux';
import * as Yup from 'yup';

import { FormikHandleChange, ShowNotificationType, User } from 'common/typings';
import { OAuthAppDetailsType, Org } from 'merchant/views/PartnerDashboard/Home/TypesDeclare/home';
import { createSubmerchantInvite } from 'merchant/views/PartnerDashboard/SubMerchant/api';
import { INVITE_TAB_TYPES } from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/InviteMerchantTabs/constants';
import {
  trackEmailFlowCTAClicked,
  trackInviteFlowFieldEditStarted,
  trackInviteFlowGenericError,
  trackInviteFlowSuccessfulInvite,
  trackInviteFlowValidationError,
  trackSubmerchantReferViaEmail,
} from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/utils/analytics';
import { TODO_PD } from 'merchant/views/PartnerDashboard/TypesDeclare';
import { showNotification } from 'merchant_common/reducers/notifications';

import SingleOAuthInviteForm from './SingleOAuthInviteForm';
import SuccessScreen from './SuccessScreen';
import { SINGLE_OAUTH_INVITE_STEPS } from './constants';

const { OAUTH_INVITE_FORM, SUCCESS_SCREEN } = SINGLE_OAUTH_INVITE_STEPS;

const commonValidations = {
  name: Yup.string()
    .trim()
    .matches(/^[a-zA-Z\s]+$/, {
      message: 'Name may only contain alphabets and spaces.',
      excludeEmptyString: true,
    })
    .min(4, 'Client Name should have at least 4 characters.')
    .required('Client Name is a required field.'),
};

const orgToValidationSchema = {
  rzp: Yup.object().shape({
    ...commonValidations,
    email: Yup.string()
      .email('Please enter a valid email id.')
      .required('Email ID is a required field.'),
    contact_no: Yup.string()
      .trim()
      .length(10, 'Please enter a valid 10-digit mobile number.')
      .nullable(),
  }),
  curlec: Yup.object().shape({
    ...commonValidations,
    // TODO v2: curlec validations
    email: Yup.string().email('Please enter a valid email id.').nullable(),
    contact_no: Yup.string()
      .trim()
      .length(10, 'Please enter a valid 10-digit mobile number.')
      .nullable(),
  }),
};

type SingleOAuthInviteProps = {
  user: User;
  org: Org;
  productType: string;
  selectedApp: OAuthAppDetailsType;
  setShowHeaderAndTabs: (args: boolean) => void;
  goToAppSelectionStep: () => void;
  onDismiss: () => void;
  onAddSuccess?: () => void;
  showNotification: ShowNotificationType;
};
const SingleOAuthInvite = ({
  user,
  org,
  productType,
  selectedApp,
  setShowHeaderAndTabs,
  goToAppSelectionStep,
  onDismiss,
  onAddSuccess = () => {},
  showNotification,
}: SingleOAuthInviteProps): JSX.Element => {
  // Tracking arg
  const inviteFlow = INVITE_TAB_TYPES.SINGLE_INVITE;

  // Steps logic
  const [currentStep, setCurrentStep] = useState(OAUTH_INVITE_FORM);
  const [isSendingInvite, setIsSendingInvite] = useState(false);
  // Formik logic and Validation
  const handleFormSubmit = (params) => {
    const { email, contact_no } = params;
    trackSubmerchantReferViaEmail({
      email,
      contact_mobile: contact_no,
      isKycAssistedSelected: false,
    });
    setIsSendingInvite(true);
    const handleErrorResponse = ({ errors = [] }) => {
      setIsSendingInvite(false);
      showNotification({
        type: 'error',
        message: errors?.[0] || 'Something went wrong',
      });

      trackInviteFlowGenericError({
        inviteFlow,
        errorMessage: errors?.[0],
        productType,
      });
    };

    createSubmerchantInvite({
      ...params,
      product: productType,
      partner_id: user.id,
      metadata: {
        application_id: selectedApp.application_id,
        client_id: selectedApp.client_id,
        oauth_referral: true,
        redirect_uri: selectedApp.redirect_uri,
        scope: 'read_write',
      },
    })
      .then((response) => {
        setIsSendingInvite(false);
        if (!response.success) {
          handleErrorResponse(response);
          return;
        }

        // external hook for setting first_submerchant_added flag
        onAddSuccess();

        trackInviteFlowSuccessfulInvite({
          inviteFlow,
          productType,
          isKycAssistedSelected: false,
        });
        setShowHeaderAndTabs(false);
        setCurrentStep(SUCCESS_SCREEN);
      })
      .catch(handleErrorResponse);
  };
  const initialValues = {
    name: '',
    contact_no: '',
    email: '',
  };
  // Load Rzp vs Curlec validation rules
  const validationSchema = orgToValidationSchema[org?.custom_code || 'rzp'];
  const formik = useFormik<FormikValues>({
    initialValues,
    validationSchema,
    validateOnChange: true,
    validateOnBlur: false,
    onSubmit: handleFormSubmit,
  });

  // Header visibility handling
  useEffect(() => {
    const screensWithoutHeaders = [SUCCESS_SCREEN];
    setShowHeaderAndTabs(!screensWithoutHeaders.includes(currentStep));
  }, [currentStep]);

  const handleChange: FormikHandleChange = ({ name, value }) => {
    if (name) {
      if (isEmpty(formik.touched)) {
        trackInviteFlowFieldEditStarted({ fieldEdited: name, inviteFlow, productType });
      }
      formik.setFieldTouched(name);
      formik.setFieldValue(name, value);
    }
  };

  const onChangeAppClick = () => {
    trackEmailFlowCTAClicked({ ctaClicked: 'Change App', productType });
    goToAppSelectionStep();
  };

  const onSendInviteClick = async () => {
    const errors = await formik.validateForm();
    if (!isEmpty(errors)) {
      const [fieldEdited, errorMessage] = Object.entries(errors)[0];
      trackInviteFlowValidationError({
        inviteFlow,
        fieldEdited,
        errorMessage,
        productType,
      });
      return;
    }
    trackEmailFlowCTAClicked({ ctaClicked: 'Send Invite', productType });

    formik.handleSubmit();
  };

  // Footer contents
  return (
    <>
      {currentStep === OAUTH_INVITE_FORM ? (
        <SingleOAuthInviteForm
          formik={formik}
          handleChange={handleChange}
          selectedApp={selectedApp}
          isSendingInvite={isSendingInvite}
          onSendInviteClick={onSendInviteClick}
          onChangeAppClick={onChangeAppClick}
        />
      ) : null}
      {currentStep === SUCCESS_SCREEN ? <SuccessScreen onDismiss={onDismiss} /> : null}
    </>
  );
};

export default compose<TODO_PD>(
  connect(
    (state) => ({ user: state.session.user, org: state.session.org }),
    (dispatch) => bindActionCreators({ showNotification }, dispatch),
  ),
)(SingleOAuthInvite);
