import React from 'react';
import { Box, Button, Divider, Text } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { compose, bindActionCreators } from 'redux';

import CheckRound from 'assets/check-round.svg';
import { CommonApiResponse, FormikHandleChange, UseFormikReturnType } from 'common/typings';
import { validatePartnerSubmerchantReferralInvitesBatch } from 'merchant/reducers/batches';
import ModalFooter from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/ModalCommon/ModalFooter';
import KYCAccessCheckbox from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/common/KYCAccessCheckbox';
import {
  trackBulkFlowCTAClicked,
  trackInviteFlowValidationError,
} from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/utils/analytics';
import { TODO_PD } from 'merchant/views/PartnerDashboard/TypesDeclare';
import { showNotification } from 'merchant_common/reducers/notifications';

import BatchValidate from './BulkAddMerchant/common/BatchValidateTyped';
import { StyledBulkAddForm } from './BulkAddMerchant/common/styled';

type BulkInviteFormProps = {
  formik: UseFormikReturnType;
  handleChange: FormikHandleChange;
  isSendingInvites: boolean;
  productType: string;
  inviteFlow: string;
  onSendInvitesClick: () => void;
  hasSelectedKycAccess: boolean | null;
  onNextClick: () => void;
  validatePartnerSubmerchantReferralInvitesBatch: () => Promise<
    CommonApiResponse<{ status: boolean }, string[]>
  >;
};
const BulkInviteForm = ({
  formik,
  handleChange,
  productType,
  inviteFlow,
  hasSelectedKycAccess,
  onNextClick,
  validatePartnerSubmerchantReferralInvitesBatch,
  isSendingInvites,
  onSendInvitesClick,
}: BulkInviteFormProps): JSX.Element => {
  const sampleUrl = '/files/sample_invite_submerchant_batch.xlsx';
  const batchType = 'partner_submerchant_referral_invite';

  const { file_id: fileId, processable_count: bulkContactsCount = 0 } = formik.values;
  const onValidation = (response, _name) => {
    if (response && response.file_id) {
      handleChange({ name: 'file_id', value: response.file_id });
      handleChange({ name: 'processable_count', value: Number(response.processable_count) });
    }
  };
  const onValidationFail = (errorMessage) => {
    trackInviteFlowValidationError({
      inviteFlow,
      fieldEdited: 'file',
      errorMessage,
      productType,
    });
  };

  const onFileRemove = () => {
    handleChange({ name: 'file_id', value: '' });
    handleChange({ name: 'processable_count', value: 0 });
  };

  const sampleFileDownloadAnalytics = () => {
    trackBulkFlowCTAClicked({ productType, ctaClicked: 'Download Sample File' });
  };
  const handleKycAccessChange = ({ isChecked }) => {
    handleChange({ name: 'request_kyc_access', value: isChecked });
  };
  return (
    <StyledBulkAddForm>
      <Box minHeight="370px">
        {/* Note: these classnames are needed for inner styling of the old BatchValidate component */}
        <div className="partner-submerchant-modal">
          <div className="modal-body">
            <BatchValidate
              batchType={batchType}
              onValidation={onValidation}
              onFileRemove={onFileRemove}
              sampleUrl={sampleUrl}
              sampleFileDownloadAnalytics={sampleFileDownloadAnalytics}
              onValidationFail={onValidationFail}
              validateBatch={validatePartnerSubmerchantReferralInvitesBatch}
              batchTypeText="text"
              maxRows={500}
              maxFileSize={52428800}
              batchClass="batch-upload-modal"
              nullStatusNotification={
                <Text size="small" color="feedback.text.negative.lowContrast">
                  {formik.errors.file_id}
                </Text>
              }
            />

            {fileId ? (
              <Box display="flex" flexDirection="column" gap="spacing.0">
                <div className="success-message flex-col-between">
                  <div>
                    <p>
                      <img src={CheckRound} alt="Tick icon" /> &nbsp;
                      {bulkContactsCount} contacts have been identified.
                    </p>
                    <span>
                      Email will be sent to {bulkContactsCount} identified contacts. Status of the
                      invite will be sent to your email address within 2 hours.
                    </span>
                  </div>
                </div>
                {/* FTUX checkbox */}
                {hasSelectedKycAccess !== null ? (
                  <Box marginTop="spacing.9" marginBottom="spacing.11">
                    <Divider />
                    <KYCAccessCheckbox
                      inviteFlow={inviteFlow}
                      productType={productType}
                      isChecked={formik.values.request_kyc_access}
                      onChange={handleKycAccessChange}
                    />
                  </Box>
                ) : null}
              </Box>
            ) : null}
          </div>
        </div>
      </Box>

      <ModalFooter>
        <Button variant="secondary" href={sampleUrl} onClick={sampleFileDownloadAnalytics}>
          Download Sample File
        </Button>

        {hasSelectedKycAccess === null ? (
          <Button onClick={onNextClick}>Next</Button>
        ) : (
          <Button isLoading={isSendingInvites} onClick={onSendInvitesClick}>
            Send Invites
          </Button>
        )}
      </ModalFooter>
    </StyledBulkAddForm>
  );
};

export default compose<TODO_PD>(
  connect(
    (state) => ({ user: state.session.user }),
    (dispatch) =>
      bindActionCreators(
        {
          showNotification,
          validatePartnerSubmerchantReferralInvitesBatch,
        },
        dispatch,
      ),
  ),
)(BulkInviteForm);
