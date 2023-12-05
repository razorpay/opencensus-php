import React from 'react';
import { Button } from '@razorpay/blade/components';
import { useMutation } from '@tanstack/react-query';
import { resendInvite } from 'merchant/views/PartnerDashboard/SubMerchant/components/AllInvitesTable/api';
import { ShowNotificationType } from 'common/typings';
import { trackAllInvitesCta } from './analytics';

type InviteActionButtonProps = {
  invite: { id: string; name: string; email: string; contact_no: string };
  showNotification: ShowNotificationType;
};

const InviteActionButton = ({
  invite: { id, name, email, contact_no },
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
    trackAllInvitesCta({ name, email, contact_no });
  };
  return (
    <Button variant="secondary" size="small" isLoading={isLoading} onClick={onResendInviteClick}>
      Resend Invite
    </Button>
  );
};

export default InviteActionButton;
