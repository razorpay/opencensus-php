import React, { useState } from 'react';
import { connect } from 'react-redux';
import { compose, bindActionCreators } from 'redux';

import { CommonApiResponse, ShowNotificationType, User } from 'common/typings';
import { analyticsTrack } from 'common/utils/analytics';
import {
  validatePartnerSubmerchantCapitalBatch,
  createPartnerSubmerchantCapitalBatch,
} from 'merchant/reducers/batches';
import { TODO_PD } from 'merchant/views/PartnerDashboard/TypesDeclare';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import { showNotification } from 'merchant_common/reducers/notifications';

import BulkInviteForm from './common/BulkAddForm';

export type BulkAddMerchantCapitalProps = {
  user: User;
  onInviteTabsBackClick: () => void;
  onDismiss: () => void;
  onAddSuccess?: () => void;
  showNotification: ShowNotificationType;
  createPartnerSubmerchantCapitalBatch: (args: {
    file_id: string;
    config: { product: string };
  }) => Promise<CommonApiResponse<{ status: boolean }, string[]>>;
  validatePartnerSubmerchantCapitalBatch: () => Promise<
    CommonApiResponse<{ status: boolean }, string[]>
  >;
};
const BulkAddMerchantCapital = ({
  user,
  onInviteTabsBackClick,
  onDismiss,
  onAddSuccess = () => {},
  showNotification,
  createPartnerSubmerchantCapitalBatch,
  validatePartnerSubmerchantCapitalBatch,
}: BulkAddMerchantCapitalProps): JSX.Element => {
  const sampleUrl = '/files/sample_capital_submerchant_batch.xlsx';
  const batchType = 'partner_submerchant_invite_capital';

  const [isSendingInvites, setIsSendingInvites] = useState(false);

  // Formik logic and Validation
  const handleFormSubmit = (params) => {
    const { file_id, processable_count: contactsCount } = params;
    // TODO v2: import from old analytics.ts
    // trackAddNewMerchantEvents('Add Multiple - Invite Contacts');
    // gaEvents.trackUploadBatch('Partner submerchant');
    setIsSendingInvites(true);

    const handleErrorResponse = ({ errors = [] }) => {
      setIsSendingInvites(false);
      showNotification({
        type: 'error',
        message: errors?.[0] || 'Failed to invite.',
      });

      // TODO v2: import from old analytics.ts
      analyticsTrack({
        screen: 'Add Merchant modal',
        objectName: 'partnerships.submerchant.add.product.group.multiple.upload',
        actionName: 'bulk upload error',
        properties: {
          error: errors?.[0],
        },
        toLumberjack: true,
      });
      analyticsTrack({
        screen: 'Add Merchant modal',
        objectName: 'partnerships.submerchant.add.product.group.multiple.invite',
        actionName: 'bulk upload error',
        properties: {
          error: errors?.[0],
        },
        toLumberjack: true,
      });
    };

    // TODO v2: import from old analytics.ts
    analyticsTrack({
      screen: 'Add Merchant modal',
      objectName: 'partnerships.capital.bulk.upload.invite',
      actionName: 'contacts button clicked',
      properties: {
        partner_id: user.id,
        contactsCount,
      },
      toLumberjack: true,
    });
    return createPartnerSubmerchantCapitalBatch?.({
      file_id,
      config: {
        product: PRODUCT_TYPE.CAPITAL,
      },
    })
      .then(() => {
        setIsSendingInvites(false);
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
      showBackButton
      handleFormSubmit={handleFormSubmit}
      sampleUrl={sampleUrl}
      batchType={batchType}
      validateBatch={validatePartnerSubmerchantCapitalBatch}
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
        {
          showNotification,
          createPartnerSubmerchantCapitalBatch,
          validatePartnerSubmerchantCapitalBatch,
        },
        dispatch,
      ),
  ),
)(BulkAddMerchantCapital);
