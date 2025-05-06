import React, { useEffect, useState } from 'react';
import { FormikValues, useFormik } from 'formik';
import { isEmpty } from 'lodash';
import { connect } from 'react-redux';
import { compose, bindActionCreators } from 'redux';
import * as Yup from 'yup';

import { FormikHandleChange, ShowNotificationType, User } from 'common/typings';
import { Org } from 'merchant/views/PartnerDashboard/Home/TypesDeclare/home';
import {
  CommonCreateSubmerchantResponse,
  createSubmerchantInvite,
} from 'merchant/views/PartnerDashboard/SubMerchant/api';
import { INVITE_TAB_TYPES } from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/InviteMerchantTabs/constants';
import {
  trackEmailFlowCTAClicked,
  trackInviteFlowFieldEditStarted,
  trackInviteFlowGenericError,
  trackInviteFlowSuccessfulInvite,
  trackInviteFlowValidationError,
  trackSubmerchantReferViaEmail,
} from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/utils/analytics';
import {
  getHasSelectedKycAccess,
  setHasSelectedKycAccess,
} from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/utils/kycAccessFtux';
import { TODO_PD } from 'merchant/views/PartnerDashboard/TypesDeclare';
import { showNotification } from 'merchant_common/reducers/notifications';

import KYCAccessForm from './KYCAccessForm';
import SingleInviteForm from './SingleInviteForm';
import SuccessScreenOptIn from './SuccessScreenOptIn';
import SuccessScreenOptOut from './SuccessScreenOptOut';
import { SINGLE_INVITE_STEPS } from './constants';

const { SINGLE_INVITE_FORM, KYC_ACCESS_FORM, SUCCESS_SCREEN_OPT_IN, SUCCESS_SCREEN_OPT_OUT } =
  SINGLE_INVITE_STEPS;

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
    request_kyc_access: Yup.boolean()
      .required('Please select one of the options here to proceed ahead')
      .nullable()
      .strict(true),
  }),
  curlec: Yup.object().shape({
    ...commonValidations,
    // TODO v2: curlec validations
    // const countryCode = user?.merchant?.country_code || 'IN';
    email: Yup.string().email('Please enter a valid email id.').nullable(),
    contact_no: Yup.string()
      .trim()
      .length(10, 'Please enter a valid 10-digit mobile number.')
      .nullable(),
  }),
};

type SingleInviteTabProps = {
  user: User;
  org: Org;
  productType: string;
  setShowHeaderAndTabs: (args: boolean) => void;
  onInviteTabsBackClick: () => void;
  onDismiss: () => void;
  onAddSuccess?: () => void;
  showNotification: ShowNotificationType;
};
const SingleInviteTab = ({
  user,
  org,
  productType,
  setShowHeaderAndTabs,
  onInviteTabsBackClick,
  onDismiss,
  onAddSuccess = () => {},
  showNotification,
}: SingleInviteTabProps): JSX.Element => {
  // Tracking arg
  const inviteFlow = INVITE_TAB_TYPES.SINGLE_INVITE;
  // FTUX logic
  const hasSelectedKycAccess = getHasSelectedKycAccess(productType, user);

  // Steps logic
  const [currentStep, setCurrentStep] = useState(SINGLE_INVITE_FORM);
  const [isSendingInvite, setIsSendingInvite] = useState(false);
  // Formik logic and Validation
  const handleFormSubmit = (params) => {
    const { email, contact_no, request_kyc_access } = params;
    trackSubmerchantReferViaEmail({
      email,
      contact_mobile: contact_no,
      isKycAssistedSelected: request_kyc_access,
      productType,
    });
    setIsSendingInvite(true);
    const handleErrorResponse = ({ errors = [] }: CommonCreateSubmerchantResponse) => {
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
      productType,
      user,
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
          isKycAssistedSelected: request_kyc_access,
        });

        if (hasSelectedKycAccess === null) {
          setShowHeaderAndTabs(false);
          setCurrentStep(
            request_kyc_access === true ? SUCCESS_SCREEN_OPT_IN : SUCCESS_SCREEN_OPT_OUT,
          );
        } else {
          showNotification({
            type: 'success',
            message: 'Invite successfully sent',
          });
          onDismiss();
        }
        // update kyc access value *after* showing appropriate success screen
        setHasSelectedKycAccess(request_kyc_access, productType);
      })
      .catch(handleErrorResponse);
  };
  const initialValues = {
    name: '',
    contact_no: '',
    email: '',
    request_kyc_access: hasSelectedKycAccess,
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
    const screensWithoutHeaders = [SUCCESS_SCREEN_OPT_IN, SUCCESS_SCREEN_OPT_OUT];
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

  const onBackClick = () => {
    trackEmailFlowCTAClicked({ ctaClicked: 'Back', productType });
    if (currentStep === SINGLE_INVITE_FORM) onInviteTabsBackClick();
    else setCurrentStep(SINGLE_INVITE_FORM);
  };

  const onNextClick = async () => {
    const errors = await formik.validateForm();
    const { request_kyc_access: _removed, ...currentStepErrors } = errors;
    if (!isEmpty(currentStepErrors)) {
      const [fieldEdited, errorMessage] = Object.entries(currentStepErrors)[0];
      trackInviteFlowValidationError({
        inviteFlow,
        fieldEdited,
        errorMessage,
        productType,
      });
      return;
    }
    trackEmailFlowCTAClicked({ ctaClicked: 'Next', productType });

    // Show no errors for loading next screen for first time
    formik.setErrors({});
    setCurrentStep(KYC_ACCESS_FORM);
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
      {currentStep === SINGLE_INVITE_FORM ? (
        <SingleInviteForm
          formik={formik}
          handleChange={handleChange}
          inviteFlow={inviteFlow}
          productType={productType}
          hasSelectedKycAccess={hasSelectedKycAccess}
          onNextClick={onNextClick}
          isSendingInvite={isSendingInvite}
          onSendInviteClick={onSendInviteClick}
          onBackClick={onBackClick}
        />
      ) : null}
      {currentStep === KYC_ACCESS_FORM ? (
        <KYCAccessForm
          formik={formik}
          handleChange={handleChange}
          productType={productType}
          isSendingInvite={isSendingInvite}
          onSendInviteClick={onSendInviteClick}
        />
      ) : null}
      {currentStep === SUCCESS_SCREEN_OPT_IN ? <SuccessScreenOptIn onDismiss={onDismiss} /> : null}
      {currentStep === SUCCESS_SCREEN_OPT_OUT ? (
        <SuccessScreenOptOut
          productType={productType}
          inviteFlow={inviteFlow}
          onDismiss={onDismiss}
        />
      ) : null}
    </>
  );
};

export default compose<TODO_PD>(
  connect(
    (state) => ({ user: state.session.user, org: state.session.org }),
    (dispatch) => bindActionCreators({ showNotification }, dispatch),
  ),
)(SingleInviteTab);
