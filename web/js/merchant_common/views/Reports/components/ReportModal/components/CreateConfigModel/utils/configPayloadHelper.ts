import { createOrCloneConfig, editConfig } from 'merchant_common/views/Reports/api/createConfigs';
import { useCreateConfigModal } from 'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/store/createConfigModalStore';
import { showNotification } from 'merchant_common/reducers/notifications';
import {
  CustomReportConfigType,
  TableNode,
  CreateOrCloneConfigPayloadType,
  CreatePayloadType,
  EditConfigPayloadType,
} from 'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/types';
import {
  alertMessages,
  errorMessages,
} from 'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/utils/constants';

const hasDuplicateValue = (columns: Set<string>, renamedColumns: Record<string, string>) => {
  return columns.size !== Object.keys(renamedColumns).length;
};

const hasValidNameFormat = (columns: Set<string>) => {
  const validColumnNameRegex = /^[A-Za-z]/;

  for (const column of columns) {
    if (!validColumnNameRegex.test(column)) {
      showNotification({
        type: 'error',
        message: alertMessages['invalidColumnNameError'],
      });
      return true;
    }
  }
  return false;
};

const hasValidationError = (renamedColumns: Record<string, string>): boolean => {
  const columns = new Set(Object.values(renamedColumns));

  if (columns.size === 0) {
    showNotification({
      type: 'error',
      message: alertMessages['columnSelectionMissingError'],
    });
    return true;
  }

  if (columns.has('')) {
    showNotification({
      type: 'error',
      message: alertMessages['emptyColumnNameError'],
    });
    return true;
  }

  if (hasDuplicateValue(columns, renamedColumns)) {
    showNotification({
      type: 'error',
      message: alertMessages['duplicateColumnNameError'],
    });
    return true;
  }

  if (hasValidNameFormat(columns)) {
    return true;
  }

  return false;
};

const createPayload = ({
  baseReport,
  reportName,
  reportDescription,
  renamedColumns,
  displayColumns,
  isEditOrClone,
}: CreatePayloadType): CustomReportConfigType => {
  const { type, source = '' } = baseReport;

  const getRenamedColumnNames = (
    displayColumns: TableNode<string>[],
    renamedColumns: Record<string, string>,
  ): string[] => {
    let columns: string[] = [];
    displayColumns.forEach((column) => {
      columns.push(renamedColumns[column.id]);
    });
    return columns;
  };

  const reverseColumnMapping = (
    renamedColumns: Record<string, string>,
  ): Record<string, string[]> => {
    let columnMapping: Record<string, string[]> = {};
    Object.entries(renamedColumns).forEach(([key, value]) => {
      if (!columnMapping[value]) {
        columnMapping[value] = [];
      }
      columnMapping[value].push(key);
    });
    return columnMapping;
  };

  const finalColumnNames = getRenamedColumnNames(displayColumns, renamedColumns);
  const fields_map = reverseColumnMapping(renamedColumns);

  return {
    ...(isEditOrClone ? {} : { type }),
    name: reportName,
    description: reportDescription,
    template: {
      fields_map: fields_map,
      output_fields: finalColumnNames,
    },
    ...(isEditOrClone ? {} : { source }),
  };
};

export const editConfigPayload = ({
  setConfigsFetchTrigger,
  handleClose,
  isEditOrClone,
  configId,
  setIsCreatingConfig,
}: EditConfigPayloadType): void | Promise<void> => {
  const { baseReport, reportName, reportDescription, renamedColumns, displayColumns } =
    useCreateConfigModal.getState();

  if (hasValidationError(renamedColumns)) return;

  const payload = createPayload({
    baseReport,
    reportName,
    reportDescription,
    renamedColumns,
    displayColumns,
    isEditOrClone,
  });

  setIsCreatingConfig(true);

  return editConfig(configId ?? '', payload)
    .then(() => {
      setConfigsFetchTrigger();
      showNotification({
        type: 'success',
        message: alertMessages['reportEditionSuccess'],
      });
      handleClose();
    })
    .catch((error) => {
      const message = errorMessages.includes(error.errors[0])
        ? error.errors[0]
        : alertMessages['reportCreationFailure'];
      showNotification({
        type: 'error',
        message: message,
      });
    })
    .finally(() => {
      setIsCreatingConfig(false);
    });
};

export const createOrCloneConfigPayload = ({
  setConfigsFetchTrigger,
  handleClose,
  setIsCreatingConfig,
  isEditOrClone,
}: CreateOrCloneConfigPayloadType): void | Promise<void> => {
  const { baseReport, reportName, reportDescription, renamedColumns, displayColumns } =
    useCreateConfigModal.getState();
  if (hasValidationError(renamedColumns)) return;

  const payload = createPayload({
    baseReport,
    reportName,
    reportDescription,
    renamedColumns,
    displayColumns,
  });

  setIsCreatingConfig(true);

  return createOrCloneConfig(payload)
    .then(() => {
      setConfigsFetchTrigger();
      showNotification({
        type: 'success',
        message: isEditOrClone
          ? alertMessages['reportCloneSuccess']
          : alertMessages['reportCreationSuccess'],
      });
      handleClose();
    })
    .catch((error) => {
      const message = errorMessages.includes(error.errors[0])
        ? error.errors[0]
        : alertMessages['reportCreationFailure'];
      showNotification({
        type: 'error',
        message: message,
      });
    })
    .finally(() => {
      setIsCreatingConfig(false);
    });
};
