import React, { useState } from 'react';
import {
  Modal,
  ModalHeader,
  ModalBody,
  ModalFooter,
  Box,
  Button,
  Divider,
} from '@razorpay/blade/components';
import { ColumnSelection } from 'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/columnConfigeration/ColumnSelection';
import { ColumnSelected } from 'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/columnConfigeration/ColumnSelected';
import { useCreateConfigModal } from 'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/store/createConfigModalStore';
import { ColumnRearrangment } from 'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/columnConfigeration/ColumnRearrangment';
import { handleValidation } from 'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/handleValidation';
import {
  createOrCloneConfigPayload,
  editConfigPayload,
} from 'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/utils/configPayloadHelper';
import { ConfirmModal } from 'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/utils/ConfirmModal';
import { SelectBaseReportType } from 'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/basicDetails/SelectBaseReportType';
import { ReportNameInput } from 'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/basicDetails/ReportNameInput';
import { ReportDescriptionInput } from 'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/basicDetails/ReportDescriptionInput';
import {
  modalTitles,
  modalDescriptions,
  totalProgressSteps,
} from 'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/utils/constants';
import { zIndicesMap } from 'common/constant';
import {
  Action,
  CreateConfigModalType,
} from 'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/types';

export const CreateConfigModel = ({
  isOpen,
  setIsOpen,
  isEditOrClone,
  id,
}: CreateConfigModalType): JSX.Element => {
  const resetSelectedFields = useCreateConfigModal((state) => state.resetSelectedFields);
  const updateStandardReportName = useCreateConfigModal((state) => state.updateStandardReportName);
  const updateReportName = useCreateConfigModal((state) => state.updateReportName);
  const updateReportDescription = useCreateConfigModal((state) => state.updateReportDescription);
  const setConfigsFetchTrigger = useCreateConfigModal((state) => state.setConfigsFetchTrigger);
  const [progressFormInView, setProgressFormInView] = useState<number>(1);
  const [isCreatingConfig, setIsCreatingConfig] = useState<boolean>(false);
  const [isOpenExitPromptModal, setIsOpenExitPromptModal] = useState<boolean>(false);

  const [isLoading, setIsLoading] = useState<boolean>(false);

  const handleClose = () => {
    setIsOpen(false);
    resetSelectedFields();
    updateStandardReportName('');
    updateReportName('');
    updateReportDescription('');
    setIsLoading(false);
    setProgressFormInView(1);
  };

  const handleConfigAction = async () => {
    if (isEditOrClone === Action.Edit) {
      await editConfigPayload({
        setConfigsFetchTrigger,
        handleClose,
        isEditOrClone,
        configId: id,
        setIsCreatingConfig,
      });
    } else {
      await createOrCloneConfigPayload({
        setConfigsFetchTrigger,
        handleClose,
        setIsCreatingConfig,
        isEditOrClone,
      });
    }
  };

  const handleModalTitle = (): string => {
    switch (isEditOrClone) {
      case Action.Edit:
        return modalTitles['edit'];
      case Action.Clone:
        return modalTitles['clone'];
      default:
        return modalTitles['create'];
    }
  };

  const handleModalDescription = (): string => {
    switch (progressFormInView) {
      case 1:
        return modalDescriptions['firstPage'];
      case 2:
        return modalDescriptions['secondPage'];
      default:
        return modalDescriptions['thirdPage'];
    }
  };

  const handleModalFooter = (): string => {
    switch (isEditOrClone) {
      case Action.Edit:
        return `${Action.Edit} ${progressFormInView}/${totalProgressSteps}`;

      case Action.Clone:
        return `${Action.Clone} ${progressFormInView}/${totalProgressSteps}`;

      default:
        return `${Action.Create} ${progressFormInView}/${totalProgressSteps}`;
    }
  };

  const manageFormValidation = (): void => {
    handleValidation({
      isEditOrClone,
      progressFormInView,
      setProgressFormInView,
      setIsLoading,
    });
  };

  return (
    <>
      {isOpenExitPromptModal ? (
        <ConfirmModal
          isOpenExitPromptModal={isOpenExitPromptModal}
          setIsOpenExitPromptModal={setIsOpenExitPromptModal}
          handleClose={handleClose}
          title={modalTitles['close']}
          description={modalDescriptions['closeModal']}
        />
      ) : null}

      <Modal
        size="medium"
        isOpen={isOpen}
        onDismiss={() => setIsOpenExitPromptModal(true)}
        zIndex={zIndicesMap.drawer}
        accessibilityLabel="custom report modal opened"
      >
        <ModalHeader title={handleModalTitle()} subtitle={handleModalDescription()} />
        <ModalBody padding="spacing.0">
          {progressFormInView === 1 ? (
            <Box
              backgroundColor="surface.background.gray.moderate"
              display="flex"
              flexDirection="column"
              padding="spacing.7"
              gap="spacing.6"
            >
              {isEditOrClone ? null : <SelectBaseReportType />}
              <ReportNameInput />
              <ReportDescriptionInput />
            </Box>
          ) : progressFormInView === 2 ? (
            <Box
              height="450px"
              display="flex"
              flexDirection="row"
              backgroundColor="surface.background.gray.moderate"
            >
              <Box overflowY="scroll" width="50%">
                <ColumnSelection />
              </Box>

              <Divider orientation="vertical" />

              <Box overflowY="scroll" width="50%">
                <ColumnSelected />
              </Box>
            </Box>
          ) : (
            <Box height="450px">
              <ColumnRearrangment />
            </Box>
          )}
        </ModalBody>

        <ModalFooter>
          <Box display="flex" justifyContent="space-between">
            <Box justifyContent="flex-start">
              {progressFormInView > 1 ? (
                <Button
                  size="medium"
                  onClick={() => setProgressFormInView(progressFormInView - 1)}
                  variant="secondary"
                >
                  Go Back
                </Button>
              ) : null}
            </Box>
            <Box justifyContent="flex-end">
              <Button
                size="medium"
                variant="tertiary"
                marginRight="spacing.4"
                onClick={() => setIsOpenExitPromptModal(true)}
              >
                Cancel
              </Button>
              {progressFormInView === 3 ? (
                <Button
                  testID="Create Button"
                  size="medium"
                  variant="primary"
                  isLoading={isCreatingConfig}
                  onClick={() => handleConfigAction()}
                >
                  {handleModalFooter()}
                </Button>
              ) : (
                <Button
                  testID="Next Button"
                  size="medium"
                  variant="primary"
                  isLoading={isLoading}
                  onClick={() => manageFormValidation()}
                >
                  Next ({progressFormInView}/{totalProgressSteps})
                </Button>
              )}
            </Box>
          </Box>
        </ModalFooter>
      </Modal>
    </>
  );
};
