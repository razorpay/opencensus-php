import React, { useEffect, useState } from 'react';
import { FormikValues, useFormik } from 'formik';
import { isEmpty } from 'lodash';
import { connect } from 'react-redux';
import { compose, bindActionCreators } from 'redux';
import * as Yup from 'yup';

import { FormikHandleChange, ShowNotificationType } from 'common/typings';
import { createPartnerSubmerchantReferralInvitesBatch } from 'merchant/reducers/batches';
import { OAuthAppDetailsType } from 'merchant/views/PartnerDashboard/Home/TypesDeclare/home';
import { CreateReferralInvitesBatchType } from 'merchant/views/PartnerDashboard/SubMerchant/api';
import { INVITE_TAB_TYPES } from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/InviteMerchantTabs/constants';
import SuccessScreen from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/SingleInviteTab/SingleOAuthInvite/SuccessScreen';
import {
  trackBulkFlowCTAClicked,
  trackInviteFlowFieldEditStarted,
  trackInviteFlowGenericError,
  trackInviteFlowSuccessfulInvite,
  trackInviteFlowValidationError,
  trackSubmerchantReferViaBulkUpload,
} from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/utils/analytics';
import { TODO_PD } from 'merchant/views/PartnerDashboard/TypesDeclare';
import { showNotification } from 'merchant_common/reducers/notifications';

import BulkInviteForm from './BulkOAuthInviteForm';
import { BULK_INVITE_STEPS } from './constants';

const { BULK_OAUTH_INVITE_FORM, SUCCESS_SCREEN } = BULK_INVITE_STEPS;

const validationSchema = Yup.object().shape({
  file_id: Yup.string()
    .required('Please upload a document (.csv or .xlsx) here to proceed ahead')
    .nullable(),
  processable_count: Yup.number().nullable(),
});

type BulkInviteTabProps = {
  productType: string;
  selectedApp: OAuthAppDetailsType;
  setShowHeaderAndTabs: (args: boolean) => void;
  onDismiss: () => void;
  onAddSuccess?: () => void;
  goToAppSelectionStep?: () => void;
  showNotification: ShowNotificationType;
  createPartnerSubmerchantReferralInvitesBatch: CreateReferralInvitesBatchType;
};
const BulkInviteTab = ({
  productType,
  selectedApp,
  setShowHeaderAndTabs,
  onDismiss,
  onAddSuccess = () => {},
  goToAppSelectionStep,
  showNotification,
  createPartnerSubmerchantReferralInvitesBatch,
}: BulkInviteTabProps): JSX.Element => {
  const inviteFlow = INVITE_TAB_TYPES.BULK_UPLOAD;

  // Steps logic
  const [currentStep, setCurrentStep] = useState(BULK_OAUTH_INVITE_FORM);
  const [isSendingInvites, setIsSendingInvites] = useState(false);

  // Header visibility handling
  useEffect(() => {
    const screensWithoutHeaders = [SUCCESS_SCREEN];
    setShowHeaderAndTabs(!screensWithoutHeaders.includes(currentStep));
  }, [currentStep]);

  // Formik logic and Validation
  const handleFormSubmit = (params) => {
    setIsSendingInvites(true);
    const { file_id, processable_count: bulkContactsCount } = params;
    trackSubmerchantReferViaBulkUpload({
      bulkContactsCount,
      isKycAssistedSelected: false,
      productType,
    });
    return createPartnerSubmerchantReferralInvitesBatch({
      file_id,
      config: {
        product: productType,
        metadata: {
          application_id: selectedApp.application_id,
          client_id: selectedApp.client_id,
          oauth_referral: true,
          redirect_uri: selectedApp.redirect_uri,
          scope: 'read_write',
        },
      },
    })
      .then(() => {
        setIsSendingInvites(false);
        trackInviteFlowSuccessfulInvite({
          inviteFlow,
          productType,
          isKycAssistedSelected: false,
        });

        setShowHeaderAndTabs(false);
        setCurrentStep(SUCCESS_SCREEN);

        onAddSuccess();
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
    processable_count: 0,
    application_id: selectedApp.application_id,
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
      {currentStep === BULK_OAUTH_INVITE_FORM ? (
        <BulkInviteForm
          formik={formik}
          handleChange={handleChange}
          productType={productType}
          selectedApp={selectedApp}
          inviteFlow={inviteFlow}
          isSendingInvites={isSendingInvites}
          goToAppSelectionStep={goToAppSelectionStep}
          onSendInvitesClick={onSendInvitesClick}
        />
      ) : null}
      {currentStep === SUCCESS_SCREEN ? <SuccessScreen onDismiss={onDismiss} /> : null}
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
