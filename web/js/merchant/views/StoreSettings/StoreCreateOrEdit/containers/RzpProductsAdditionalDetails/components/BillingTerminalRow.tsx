import React, { useState, useEffect } from 'react';
import {
  Box,
  Button,
  Card,
  Link,
  Text,
  EditIcon,
  TrashIcon,
  CardBody,
  Switch,
  useToast,
} from '@razorpay/blade/components';
import { ArrayHelpers, useField } from 'formik';
import { useSearchParams } from 'react-router-dom';

import StatusChangeAlertModal from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/TerminalsTableContainer/components/StatusChangeAlertModal';
import useStoreTerminalCreateMutation from 'merchant/views/StoreSettings/StoreCreateOrEdit/hooks/useStoreTerminalCreateMutation';
import useStoreTerminalUpdateMutation from 'merchant/views/StoreSettings/StoreCreateOrEdit/hooks/useStoreTerminalUpdateMutation';
import { useStoresCreateStore } from 'merchant/views/StoreSettings/StoreCreateOrEdit/stores/storesCreateFormStore';
import { BillingTerminal } from 'merchant/views/StoreSettings/StoreCreateOrEdit/types';
import FormikTextInputField from 'merchant/views/StoreSettings/common/components/FormFields/FormikTextInputField';

const BillingTerminalRow = ({
  namePrefix,
  arrayHelpers,
  index,
  terminalId,
  isActive,
}: {
  namePrefix: string;
  arrayHelpers: ArrayHelpers;
  index: number;
  terminalId?: string;
  isActive?: boolean;
}) => {
  const [isEditing, setIsEditing] = useState(!terminalId);
  const [prevValues, setPrevValues] = useState<BillingTerminal>();
  const [shouldShowAlertModal, setShouldShowAlertModal] = useState(false);

  const [searchParams] = useSearchParams();
  const toast = useToast();
  const { setDeleteTerminalModal } = useStoresCreateStore();

  const { createStoreTerminal, isLoading: isCreateStoreTerminalLoading } =
    useStoreTerminalCreateMutation({
      onSuccessHandler: () => {},
      onErrorHandler: () => {},
    });

  const { updateStoreTerminal, isLoading: isUpdateStoreTerminalLoading } =
    useStoreTerminalUpdateMutation({
      onSuccessHandler: () => {},
      onErrorHandler: () => {},
    });

  const storeId = searchParams.get('id');
  const [, meta, helpers] = useField<BillingTerminal>(namePrefix);
  const { setValue } = helpers;

  useEffect(() => {
    setValue({ ...meta.value, isEditing });
  }, [isEditing]);
  useEffect(() => {
    if (terminalId) {
      setPrevValues(meta.initialValue || meta.value);
    }
  }, [terminalId]);

  const handleUpdateStoreTerminal = (isStatusChanged: boolean) => {
    if (terminalId && storeId)
      updateStoreTerminal(
        {
          id: terminalId,
          storeId,
          name: prevValues?.name !== meta?.value?.name ? meta?.value?.name : undefined,
          macAddress: meta?.value?.macAddress,
          ipAddress: meta?.value?.ipAddress,
          isActive: isStatusChanged ? !isActive : isActive,
        },
        {
          onSuccess: (data) => {
            if (data?.storeTerminalUpdate?.success === false) {
              toast.show({
                type: 'informational',
                color: 'negative',
                content: data?.storeTerminalUpdate?.message,
              });
              return;
            }
            toast.show({
              type: 'informational',
              color: 'positive',
              content: 'Terminal has been updated',
            });
            const updatedTerminalInfo = {
              name: data?.storeTerminalUpdate?.storeTerminal?.name,
              macAddress: data?.storeTerminalUpdate?.storeTerminal?.terminalInfo?.macAddress,
              ipAddress: data?.storeTerminalUpdate?.storeTerminal?.terminalInfo?.ipAddress,
              id: terminalId,
              licenseKey: data?.storeTerminalUpdate?.storeTerminal?.terminalInfo?.licenseKey,
              isActive: data?.storeTerminalUpdate?.storeTerminal?.isActive,
            };
            setValue({
              ...meta?.value,
              ...updatedTerminalInfo,
            });
            setIsEditing(false);
            setPrevValues(updatedTerminalInfo);
          },
          onError: () => {
            toast.show({
              type: 'informational',
              color: 'negative',
              content: 'Something went wrong!',
            });
          },
        },
      );
  };

  const handleStatusToggleChange = (updatedStatus: boolean) => {
    if (
      !updatedStatus &&
      window.sessionStorage.getItem('storeTerminalStatusAlertAcknowledged') !== 'true'
    ) {
      setShouldShowAlertModal(true);
    } else {
      handleUpdateStoreTerminal(true);
    }
  };

  const handleStatusChangeModalSubmission = () => {
    handleUpdateStoreTerminal(true);
    setShouldShowAlertModal(false);
    window.sessionStorage.setItem('storeTerminalStatusAlertAcknowledged', 'true');
  };

  const onSave = () => {
    if (terminalId) {
      handleUpdateStoreTerminal(false);
      return;
    }
    if (storeId)
      createStoreTerminal(
        {
          storeId,
          name: meta?.value?.name,
          macAddress: meta?.value?.macAddress,
          ipAddress: meta?.value?.ipAddress,
          type: 'BILLING',
        },
        {
          onSuccess: (data) => {
            if (data?.storeTerminalCreate?.success === false) {
              toast.show({
                type: 'informational',
                color: 'negative',
                content: data?.storeTerminalCreate?.message,
              });
              return;
            }
            toast.show({
              type: 'informational',
              color: 'positive',
              content: 'Terminal has been created',
            });
            const newTerminalInfo = {
              name: data?.storeTerminalCreate?.storeTerminal?.name,
              macAddress: data?.storeTerminalCreate?.storeTerminal?.terminalInfo?.macAddress,
              ipAddress: data?.storeTerminalCreate?.storeTerminal?.terminalInfo?.ipAddress,
              id: data?.storeTerminalCreate?.storeTerminal?.id,
              licenseKey: data?.storeTerminalCreate?.storeTerminal?.terminalInfo?.licenseKey,
              isActive: data?.storeTerminalCreate?.storeTerminal?.isActive,
            };
            setValue({
              ...newTerminalInfo,
            });
            setIsEditing(false);
            setPrevValues(newTerminalInfo);
          },
          onError: () => {
            toast.show({
              type: 'informational',
              color: 'negative',
              content: 'Something went wrong!',
            });
          },
        },
      );
  };

  return (
    <>
      {shouldShowAlertModal && (
        <StatusChangeAlertModal
          modalProps={{
            isOpen: shouldShowAlertModal,
            onDismiss: () => setShouldShowAlertModal(false),
          }}
          onSubmit={handleStatusChangeModalSubmission}
        />
      )}
      <Card elevation="none" borderRadius="medium" data-analytics-name="terminal-section">
        <CardBody>
          <Box display="flex" gap="spacing.5" flexDirection="column">
            <Box display="flex" justifyContent="space-between">
              <Box display="flex" alignItems="center" gap="spacing.5">
                <Text>Terminal {index + 1}</Text>
                {terminalId && !isEditing && (
                  <Switch
                    accessibilityLabel="Toggle terminal status"
                    size="medium"
                    onChange={() => handleStatusToggleChange(!isActive)}
                    isChecked={isActive}
                    isDisabled={isUpdateStoreTerminalLoading}
                  />
                )}
              </Box>
              {terminalId && !isEditing ? (
                <Box display="flex" gap="spacing.4">
                  <Link
                    isDisabled={isCreateStoreTerminalLoading || isUpdateStoreTerminalLoading}
                    icon={TrashIcon}
                    onClick={() => {
                      setDeleteTerminalModal({
                        isOpen: true,
                        terminalId,
                        index,
                      });
                    }}
                    color="negative"
                    variant="button"
                    data-analytics-name="remove-terminal"
                  >
                    Remove
                  </Link>
                  <Link
                    isDisabled={isUpdateStoreTerminalLoading}
                    icon={EditIcon}
                    onClick={() => setIsEditing(true)}
                    variant="button"
                    data-analytics-name="edit-terminal"
                  >
                    Edit
                  </Link>
                </Box>
              ) : null}
            </Box>
            {terminalId ? (
              <FormikTextInputField name={`${namePrefix}.licenseKey`} label="" isDisabled />
            ) : null}
            <Box display="grid" gridTemplateColumns="1fr 1fr 1fr" gap="spacing.5">
              <FormikTextInputField
                name={`${namePrefix}.name`}
                label="Name"
                placeholder="Enter Name"
                accessibilityLabel="required"
                isRequired
                necessityIndicator="required"
                isDisabled={!isEditing}
                validationState={
                  (prevValues?.name && meta?.value?.name?.length === 0) ||
                  meta?.value?.name?.length > 50
                    ? 'error'
                    : 'none'
                }
                errorText={
                  meta?.value?.name?.length === 0
                    ? 'Terminal name cannot be empty'
                    : "Name can't be more than 50 characters"
                }
              />
              <FormikTextInputField
                name={`${namePrefix}.macAddress`}
                label="MAC Address"
                placeholder="Enter MAC Address"
                isDisabled={!isEditing}
                validationState={meta?.value?.macAddress?.length > 17 ? 'error' : 'none'}
                errorText={"MAC Address can't be more than 17 characters"}
              />
              <FormikTextInputField
                name={`${namePrefix}.ipAddress`}
                label="IP Address"
                placeholder="Enter IP Address"
                isDisabled={!isEditing}
                validationState={meta?.value?.ipAddress?.length > 15 ? 'error' : 'none'}
                errorText={"IP Address can't be more than 15 characters"}
              />
            </Box>
            {isEditing ? (
              <Box display="flex" justifyContent="end" gap="spacing.5">
                <Button
                  variant="secondary"
                  isDisabled={isCreateStoreTerminalLoading || isUpdateStoreTerminalLoading}
                  color="negative"
                  onClick={() => {
                    if (!terminalId) {
                      arrayHelpers.remove(index);
                    } else if (prevValues) {
                      setValue(prevValues);
                    }
                    setIsEditing(false);
                  }}
                  data-analytics-name="cancel-terminal-changes"
                >
                  Cancel
                </Button>
                <Button
                  variant="secondary"
                  isLoading={isCreateStoreTerminalLoading || isUpdateStoreTerminalLoading}
                  isDisabled={
                    !meta?.value?.name?.length ||
                    meta?.value?.name?.length > 50 ||
                    meta?.value?.macAddress?.length > 17 ||
                    meta?.value?.ipAddress?.length > 15
                  }
                  onClick={onSave}
                  data-analytics-name="save-terminal-changes"
                >
                  Save
                </Button>
              </Box>
            ) : null}
          </Box>
        </CardBody>
      </Card>
    </>
  );
};
export default BillingTerminalRow;
