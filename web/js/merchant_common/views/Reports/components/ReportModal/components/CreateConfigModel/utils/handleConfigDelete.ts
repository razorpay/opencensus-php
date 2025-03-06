import { deleteConfig } from 'merchant_common/views/Reports/api/createConfigs';
import { showNotification } from 'merchant_common/reducers/notifications';
import { HandleConfigDeleteType } from 'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/types';

export const handleConfigDelete = async ({
  configId,
  setIsOpenExitPromptModal,
  setConfigsFetchTrigger,
}: HandleConfigDeleteType): Promise<void> => {
  return deleteConfig(configId)
    .then(() => {
      setConfigsFetchTrigger();
      showNotification({
        type: 'success',
        message: 'Your Custom Report has been deleted Successfuly',
      });
      setIsOpenExitPromptModal(false);
    })
    .catch((error) => {
      showNotification({
        type: 'error',
        message: 'Failed to delete your custom report. Please try again later.',
      });
    });
};
