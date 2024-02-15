import React from 'react';
import { Button } from '@razorpay/blade/components';
import { useMutation } from '@tanstack/react-query';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { ShowNotificationType } from 'common/typings';
import {
  SubmerchantInviteItem,
  resendInvite,
} from 'merchant/views/PartnerDashboard/SubMerchant/components/AllInvitesTable/api';
import { showNotification } from 'merchant_common/reducers/notifications';

import { trackAllInvitesCta } from './analytics';

type InviteActionButtonProps = {
  invite: SubmerchantInviteItem;
  productType: string;
  showNotification: ShowNotificationType;
};

const InviteActionButton = ({
  invite: { id, name, email, contact_no },
  productType,
  showNotification,
}: InviteActionButtonProps): JSX.Element => {
  const { mutate: handleResendInvite, isLoading } = useMutation({
    mutationFn: resendInvite,
    onError: (err: { errors: Array<string> }) => {
      showNotification?.({
        type: 'error',
        message: err.errors?.[0],
      });
    },
    onSuccess: () => {
      showNotification?.({
        type: 'success',
        message: `Invite is resent successfully`,
      });
    },
  });

  const onResendInviteClick = () => {
    handleResendInvite(id);
    trackAllInvitesCta({ name, email, contact_no, productType });
  };
  return (
    <Button variant="secondary" size="small" isLoading={isLoading} onClick={onResendInviteClick}>
      Resend Invite
    </Button>
  );
};

export default connect(
  () => ({}),
  (dispatch) => bindActionCreators({ showNotification }, dispatch),
)(InviteActionButton);
