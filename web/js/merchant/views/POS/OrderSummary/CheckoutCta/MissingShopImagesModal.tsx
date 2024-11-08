import React from 'react';
import {
  Button,
  Modal,
  ModalBody,
  ModalHeader,
  ModalFooter,
  Text,
  useToast,
} from '@razorpay/blade/components';
import { useMutation } from '@tanstack/react-query';
import { initiatePosOnboarding as initiatePosOnboardingFn } from 'merchant/views/POS/services';
import { useSplitzService } from 'common/splitz';
import { isExperimentEnabled } from 'common/splitz/utils';

type MissingShopImagesModalProps = {
  isOpen: boolean;
  externalUrl: string | null;
  onClose: () => void;
};

const MissingShopImagesModal = ({
  isOpen,
  externalUrl,
  onClose,
}: MissingShopImagesModalProps): JSX.Element => {
  const toast = useToast();
  const { mutate: initiatePosOnboarding, status } = useMutation({
    mutationFn: () => initiatePosOnboardingFn(),
    onSuccess: () => {
      if (externalUrl) {
        window.location.assign(externalUrl);
      }
    },
    onError: (error) => {
      toast.show({
        content: error,
        color: 'negative',
        autoDismiss: true,
      });
    },
  });
  const { abExperiments } = useSplitzService();

  const isPOSEnabledForAPIMerchant = isExperimentEnabled(abExperiments.pos_api_merchant_enablement);

  const handleOnAddDetailsClick = () => {
    if (isPOSEnabledForAPIMerchant) {
      initiatePosOnboarding();
    } else {
      if (externalUrl) {
        window.location.assign(externalUrl);
      }
    }
  };

  return (
    <Modal isOpen={isOpen} onDismiss={onClose} size="small">
      <ModalHeader title="Add your POS Details" />
      <ModalBody>
        <Text size="medium">
          Please add few more details in order to complete your order. Without these, we won’t be
          able to process your POS order.
        </Text>
      </ModalBody>
      <ModalFooter>
        <Button onClick={handleOnAddDetailsClick} isLoading={status === 'loading'}>
          Add Details
        </Button>
      </ModalFooter>
    </Modal>
  );
};

export default MissingShopImagesModal;
