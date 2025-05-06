import React, { useEffect, useState } from 'react';
import { FormikValues, useFormik } from 'formik';
import { isEmpty } from 'lodash';
import { connect } from 'react-redux';
import { compose, bindActionCreators } from 'redux';
import * as Yup from 'yup';

import { FormikHandleChange, ShowNotificationType, User } from 'common/typings';
import { createPartnerSubmerchantReferralInvitesBatch } from 'merchant/reducers/batches';
import { CreateReferralInvitesBatchType } from 'merchant/views/PartnerDashboard/SubMerchant/api';
import { INVITE_TAB_TYPES } from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/InviteMerchantTabs/constants';
import SuccessScreenOptIn from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/SingleInviteTab/SuccessScreenOptIn';
import SuccessScreenOptOut from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/SingleInviteTab/SuccessScreenOptOut';
import {
  trackBulkFlowCTAClicked,
  trackInviteFlowFieldEditStarted,
  trackInviteFlowGenericError,
  trackInviteFlowSuccessfulInvite,
  trackInviteFlowValidationError,
  trackSubmerchantReferViaBulkUpload,
} from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/utils/analytics';
import {
  getHasSelectedKycAccess,
  setHasSelectedKycAccess,
} from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/utils/kycAccessFtux';
import { TODO_PD } from 'merchant/views/PartnerDashboard/TypesDeclare';
import { showNotification } from 'merchant_common/reducers/notifications';

import BulkInviteForm from './BulkInviteForm';
import KYCAccessForm from './KYCAccessForm';
import { BULK_INVITE_STEPS } from './constants';

const { BULK_INVITE_FORM, KYC_ACCESS_FORM, SUCCESS_SCREEN_OPT_IN, SUCCESS_SCREEN_OPT_OUT } =
  BULK_INVITE_STEPS;

const validationSchema = Yup.object().shape({
  file_id: Yup.string()
    .required('Please upload a document (.csv or .xlsx) here to proceed ahead')
    .nullable(),
  request_kyc_access: Yup.boolean()
    .required('Please select one of the options here to proceed ahead')
    .nullable()
    .strict(true),
  processable_count: Yup.number().nullable(),
});

type BulkInviteTabProps = {
  user: User;
  productType: string;
  setShowHeaderAndTabs: (args: boolean) => void;
  onDismiss: () => void;
  onAddSuccess?: () => void;
  showNotification: ShowNotificationType;
  createPartnerSubmerchantReferralInvitesBatch: CreateReferralInvitesBatchType;
};
const BulkInviteTab = ({
  user,
  productType,
  setShowHeaderAndTabs,
  onDismiss,
  onAddSuccess = () => {},
  showNotification,
  createPartnerSubmerchantReferralInvitesBatch,
}: BulkInviteTabProps): JSX.Element => {
  const inviteFlow = INVITE_TAB_TYPES.BULK_UPLOAD;
  // FTUX logic
  const hasSelectedKycAccess = getHasSelectedKycAccess(productType, user);

  // Steps logic
  const [currentStep, setCurrentStep] = useState(BULK_INVITE_FORM);
  const [isSendingInvites, setIsSendingInvites] = useState(false);

  // Header visibility handling
  useEffect(() => {
    const screensWithoutHeaders = [SUCCESS_SCREEN_OPT_IN, SUCCESS_SCREEN_OPT_OUT];
    setShowHeaderAndTabs(!screensWithoutHeaders.includes(currentStep));
  }, [currentStep]);

  // Formik logic and Validation
  const handleFormSubmit = (params) => {
    // trackAddNewMerchantEvents('Add Multiple - Invite Contacts');
    // gaEvents.trackUploadBatch('Partner submerchant');
    setIsSendingInvites(true);
    const { file_id, request_kyc_access, processable_count: bulkContactsCount } = params;
    trackSubmerchantReferViaBulkUpload({
      bulkContactsCount,
      isKycAssistedSelected: request_kyc_access,
      productType,
    });
    return createPartnerSubmerchantReferralInvitesBatch({
      file_id,
      config: {
        product: productType,
      },
    })
      .then(() => {
        setIsSendingInvites(false);
        trackInviteFlowSuccessfulInvite({
          inviteFlow,
          productType,
          isKycAssistedSelected: request_kyc_access,
        });

        if (hasSelectedKycAccess === null) {
          setShowHeaderAndTabs(false);
          setCurrentStep(
            params.request_kyc_access === true ? SUCCESS_SCREEN_OPT_IN : SUCCESS_SCREEN_OPT_OUT,
          );
        } else {
          showNotification({
            type: 'success',
            message: 'Invites successfully sent',
          });
          onDismiss();
        }
        // update kyc access value *after* showing appropriate success screen
        setHasSelectedKycAccess(params.request_kyc_access, productType);

        onAddSuccess();
        // trackBulkSuccess();
      })
      .catch(({ errors = [] }) => {
        setIsSendingInvites(false);
        showNotification({
          type: 'error',
          message: errors?.[0] || 'Failed to invite.',
        });

        trackInviteFlowGenericError({
          inviteFlow,
          errorMessage: errors?.[0],
          productType,
        });
      });
  };
  const initialValues = {
    file_id: '',
    request_kyc_access: hasSelectedKycAccess,
    processable_count: 0,
  };

  const formik = useFormik<FormikValues>({
    initialValues,
    validationSchema,
    validateOnChange: true,
    validateOnBlur: false,
    onSubmit: handleFormSubmit,
  });

  const handleChange: FormikHandleChange = ({ name, value }) => {
    if (name) {
      if (isEmpty(formik.touched)) {
        trackInviteFlowFieldEditStarted({ fieldEdited: name, inviteFlow, productType });
      }
      formik.setFieldTouched(name);
      formik.setFieldValue(name, value);
    }
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
    trackBulkFlowCTAClicked({ productType, ctaClicked: 'Next' });
    // Show no errors for loading next screen for first time
    formik.setErrors({});
    setCurrentStep(KYC_ACCESS_FORM);
  };
  const onSendInvitesClick = async () => {
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
    trackBulkFlowCTAClicked({ productType, ctaClicked: 'Send Invites' });
    formik.handleSubmit();
  };
  // Footer contents
  return (
    <>
      {currentStep === BULK_INVITE_FORM ? (
        <BulkInviteForm
          formik={formik}
          handleChange={handleChange}
          productType={productType}
          inviteFlow={inviteFlow}
          hasSelectedKycAccess={hasSelectedKycAccess}
          onNextClick={onNextClick}
          isSendingInvites={isSendingInvites}
          onSendInvitesClick={onSendInvitesClick}
        />
      ) : null}
      {currentStep === KYC_ACCESS_FORM ? (
        <KYCAccessForm
          formik={formik}
          handleChange={handleChange}
          productType={productType}
          isSendingInvites={isSendingInvites}
          onSendInvitesClick={onSendInvitesClick}
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
    (state) => ({ user: state.session.user }),
    (dispatch) =>
      bindActionCreators(
        { showNotification, createPartnerSubmerchantReferralInvitesBatch },
        dispatch,
      ),
  ),
)(BulkInviteTab);
