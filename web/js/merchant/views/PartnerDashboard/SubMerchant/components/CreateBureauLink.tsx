import React, { useState } from 'react';
import {
  Button,
  Modal,
  ModalBody,
  ModalHeader,
  ModalFooter,
  Box,
  Alert,
  Text,
} from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';

import { ShowNotificationType } from 'common/typings';
import copyToClipboard from 'common/utils/copyToClipboard';
import { sendMessage } from 'merchant/views/PartnerDashboard/SubMerchant/api';
import {
  CREATE_BUREAU_COUNTDOWN_TIME,
  SMS_COUNT_MAX_LIMIT,
} from 'merchant/views/PartnerDashboard/constants';

type CreateBureauLinkProps = {
  closeModal: () => void;
  bureauLinkData: {
    bureauLink: string;
    smsCount: number;
    partnerId: string;
    merchantId: string;
  };
  showNotification: ShowNotificationType;
};
export const CreateBureauLink = ({
  closeModal,
  bureauLinkData,
  showNotification,
}: CreateBureauLinkProps): JSX.Element => {
  const { bureauLink, smsCount, partnerId, merchantId } = bureauLinkData;

  const [sendMessageCount, setSendMessageCount] = useState<number>(smsCount);
  const [shouldShowSuccessAlert, setShouldShowSuccessAlert] = useState(false);
  const [isSendSmsDisabled, setIsSendSmsDisabled] = useState(false);

  const { isFetching, refetch: sendSms } = useQuery({
    queryKey: ['send-bureau-link-sms'],
    queryFn: () => sendMessage(partnerId, merchantId, bureauLink),
    refetchOnWindowFocus: false,
    enabled: false,
    onSuccess: (data) => {
      setIsSendSmsDisabled(true);
      setTimeout(() => {
        setIsSendSmsDisabled(false);
      }, CREATE_BUREAU_COUNTDOWN_TIME);
      if (data.data?.status) {
        setSendMessageCount(data.data?.sms_count || smsCount);
        setShouldShowSuccessAlert(true);
      } else {
        showNotification?.({
          type: 'error',
          message: 'Sending Link via SMS Failed!',
        });
      }
    },
    onError: (err: { errors: Array<string> }) => {
      showNotification?.({
        type: 'error',
        message: err.errors,
      });
    },
  });

  const handleCopyLink = () => {
    copyToClipboard(bureauLink);
  };

  const handleSendSms = () => {
    sendSms();
  };

  return (
    <Modal isOpen={true} onDismiss={closeModal}>
      <ModalHeader
        title="Line Of Credit Bureau"
        subtitle="We can send out the link to the client as an SMS on your behalf or copy the link and send it out manually"
      />
      <ModalBody>
        <Box
          display="flex"
          gap="spacing.5"
          alignItems="center"
          padding={['spacing.4', 'spacing.3']}
          marginBottom="spacing.6"
          flex="1"
          backgroundColor="surface.background.gray.subtle"
        >
          <Text weight="semibold">{bureauLink}</Text>
        </Box>
        {shouldShowSuccessAlert ? (
          <Alert
            isDismissible={false}
            title="SMS sent"
            description="An SMS has been successfully sent to your client on their contact number."
            color="positive"
          />
        ) : null}
      </ModalBody>
      <ModalFooter>
        <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
          <Button variant="secondary" onClick={() => handleCopyLink()}>
            Copy Link
          </Button>
          <Button
            onClick={handleSendSms}
            isLoading={isFetching}
            isDisabled={isSendSmsDisabled || sendMessageCount === SMS_COUNT_MAX_LIMIT}
          >
            {sendMessageCount === 0 ? 'Send Link as SMS' : 'Resend Link as SMS'}
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};
