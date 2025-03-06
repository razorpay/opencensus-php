import {
  Alert,
  Box,
  Button,
  Modal,
  ModalBody,
  ModalFooter,
  ModalHeader,
} from '@razorpay/blade/components';
import React, { useState } from 'react';
import { handleConfigDelete } from 'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/utils/handleConfigDelete';
import { useCreateConfigModal } from 'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/store/createConfigModalStore';
import { zIndicesMap } from 'common/constant';
import { ConfirmModalType } from 'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/types';

export const ConfirmModal = ({
  isOpenExitPromptModal,
  setIsOpenExitPromptModal,
  handleClose,
  title,
  description,
  isDeleteConfig,
  configId,
}: ConfirmModalType): JSX.Element => {
  const [isDeletingConfig, setIsDeletingConfig] = useState<boolean>(false);
  const setConfigsFetchTrigger = useCreateConfigModal((state) => state.setConfigsFetchTrigger);

  const exitAndCloseModal = (): void => {
    setIsOpenExitPromptModal(false);
    if (handleClose) handleClose();
  };

  const handleDeleteConfigClick = async () => {
    setIsDeletingConfig(true);
    await handleConfigDelete({
      configId: configId ?? '',
      setIsOpenExitPromptModal,
      setIsDeletingConfig,
      setConfigsFetchTrigger,
    });
    setIsDeletingConfig(false);
  };

  return (
    <Modal
      size="small"
      isOpen={isOpenExitPromptModal}
      onDismiss={() => setIsOpenExitPromptModal(false)}
      zIndex={zIndicesMap.modalOverlay}
    >
      <ModalHeader title={title} subtitle={description} />
      <ModalBody>
        <Box>
          <Alert
            title="Important"
            description={
              isDeleteConfig ? 'This is an irreversible action' : 'Progress will not be saved'
            }
            marginTop="spacing.4"
            color="negative"
            isDismissible={false}
          />
        </Box>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" justifyContent="flex-end">
          <Button
            accessibilityLabel="Cancel"
            size="small"
            onClick={() => setIsOpenExitPromptModal(false)}
            variant="tertiary"
          >
            Cancel
          </Button>

          <Button
            isLoading={isDeleteConfig && isDeletingConfig}
            size="small"
            onClick={() => {
              isDeleteConfig ? handleDeleteConfigClick() : exitAndCloseModal();
            }}
            marginLeft="spacing.4"
            iconPosition="left"
          >
            {isDeleteConfig ? 'Delete' : 'Discard'}
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};
