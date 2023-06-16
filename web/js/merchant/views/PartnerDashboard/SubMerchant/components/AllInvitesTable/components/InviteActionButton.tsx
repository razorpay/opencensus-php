import React from 'react';
import { Button } from '@razorpay/blade/components';
import { useMutation } from 'react-query';
import { resendInvite } from 'merchant/views/PartnerDashboard/SubMerchant/components/AllInvitesTable/api';
import { ShowNotificationType } from 'common/typings';

type InviteActionButtonProps = {
  invite: { id: string };
  showNotification: ShowNotificationType;
};

const InviteActionButton = ({ invite, showNotification }: InviteActionButtonProps): JSX.Element => {
  const [handleResendInvite, { isLoading }] = useMutation(resendInvite, {
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
  return (
    <Button
      variant="secondary"
      size="small"
      isLoading={isLoading}
      onClick={() => handleResendInvite(invite.id)}
    >
      Resend Invite
    </Button>
  );
};

export default InviteActionButton;
