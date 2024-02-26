import React, { useState } from 'react';
import { connect } from 'react-redux';
import { compose, bindActionCreators } from 'redux';

import { ShowNotificationType } from 'common/typings';
import {
  createPartnerSubmerchantBatch,
  validatePartnerSubmerchantBatch,
} from 'merchant/reducers/batches';
import { CommonSubmerchantBatchResponse } from 'merchant/views/PartnerDashboard/SubMerchant/api';
import { TODO_PD } from 'merchant/views/PartnerDashboard/TypesDeclare';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import { showNotification } from 'merchant_common/reducers/notifications';

import BulkInviteForm from './common/BulkAddForm';

export type BulkAddMerchantPGProps = {
  onInviteTabsBackClick: () => void;
  onDismiss: () => void;
  onAddSuccess?: () => void;
  showNotification: ShowNotificationType;
  createPartnerSubmerchantBatch: (args: {
    file_id: string;
    config: { product: string };
  }) => Promise<CommonSubmerchantBatchResponse>;
  validatePartnerSubmerchantBatch: () => Promise<CommonSubmerchantBatchResponse>;
};
const BulkAddMerchantPG = ({
  onInviteTabsBackClick,
  onDismiss,
  onAddSuccess = () => {},
  showNotification,
  createPartnerSubmerchantBatch,
  validatePartnerSubmerchantBatch,
}: BulkAddMerchantPGProps): JSX.Element => {
  const sampleUrl = '/files/sample_submerchant_batch.xlsx';
  const batchType = 'partner_submerchant_invite';

  const [isSendingInvites, setIsSendingInvites] = useState(false);

  // Formik logic and Validation
  const handleFormSubmit = (params) => {
    // trackAddNewMerchantEvents('Add Multiple - Invite Contacts');
    // gaEvents.trackUploadBatch('Partner submerchant');
    setIsSendingInvites(true);
    const handleErrorResponse = ({ errors }: CommonSubmerchantBatchResponse) => {
      setIsSendingInvites(false);
      showNotification({
        type: 'error',
        message: errors?.[0] || 'Failed to invite.',
      });
      // TODO v2: import from old analytics.ts
    };
    const { file_id } = params;
    return createPartnerSubmerchantBatch?.({
      file_id,
      config: {
        product: PRODUCT_TYPE.PG,
      },
    })
      .then(() => {
        setIsSendingInvites(false);
        // TODO v2: import from old analytics.ts
        showNotification({
          type: 'success',
          message:
            'Your file has been successfully processed. Status of account creation will be sent to you within 2 hours.',
        });

        onAddSuccess();
        onDismiss();
      })
      .catch(handleErrorResponse);
  };

  const onValidationSuccess = () => {
    // TODO v2: import from old analytics.ts
  };

  const onValidationFail = () => {
    // TODO v2: import from old analytics.ts
  };
  const sampleFileDownloadAnalytics = () => {
    // TODO v2: import from old analytics.ts
  };
  const clickToUploadAnalytics = () => {
    // TODO v2: import from old analytics.ts
  };

  return (
    <BulkInviteForm
      showBackButton={false}
      handleFormSubmit={handleFormSubmit}
      sampleUrl={sampleUrl}
      batchType={batchType}
      validateBatch={validatePartnerSubmerchantBatch}
      isSendingInvites={isSendingInvites}
      onValidationSuccess={onValidationSuccess}
      onValidationFail={onValidationFail}
      sampleFileDownloadAnalytics={sampleFileDownloadAnalytics}
      clickToUploadAnalytics={clickToUploadAnalytics}
      onBackClick={onInviteTabsBackClick}
    />
  );
};

export default compose<TODO_PD>(
  connect(
    (state) => ({ user: state.session.user }),
    (dispatch) =>
      bindActionCreators(
        { showNotification, createPartnerSubmerchantBatch, validatePartnerSubmerchantBatch },
        dispatch,
      ),
  ),
)(BulkAddMerchantPG);
