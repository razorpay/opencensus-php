import React from 'react';
import {
  Box,
  Button,
  CloseIcon,
  Card,
  CardBody,
  Text,
  ArrowRightIcon,
  Divider,
} from '@razorpay/blade/components';
import { ConfigProcessSelectionProp } from 'merchant/views/Reconciliations/Dashboard/types';

const ConfigProcessSelection: React.FC<ConfigProcessSelectionProp> = ({
  selectedProcessesItem,
  isProcessesSelectionDone,
  setOpenProcessSelectionModal,
  removeProcessCardHandler,
  setHasCompletedCreateReportStep,
}) => {
  return (
    <Box display="flex" flexDirection="column" gap="spacing.4" paddingY="spacing.4">
      {selectedProcessesItem.length >= 0 && isProcessesSelectionDone === false ? (
        <Box
          width="550px"
          borderRadius="medium"
          borderWidth="thin"
          borderColor="surface.border.gray.muted"
          pointerEvents="cursor"
          marginY="spacing.6"
        >
          <Card
            padding="spacing.4"
            onClick={() => {
              setOpenProcessSelectionModal(true);
            }}
            elevation="none"
          >
            <CardBody>
              <Box
                display="flex"
                justifyContent="space-between"
                alignItems="center"
                gap="spacing.2"
              >
                <Box display="flex" flexDirection="column" gap="spacing.2" padding="spacing.5">
                  <Text weight="semibold" size="medium" color="surface.text.gray.normal">
                    Add Process
                  </Text>
                  <Text color="surface.text.gray.subtle" size="small">
                    Select the source data you want
                  </Text>
                </Box>
                <Button
                  icon={ArrowRightIcon}
                  onClick={() => {
                    setOpenProcessSelectionModal(true);
                  }}
                  size="medium"
                  variant="tertiary"
                />
              </Box>
            </CardBody>
          </Card>
        </Box>
      ) : (
        <Box display="grid" gridTemplateColumns="repeat(2, 1fr)" gap="spacing.4">
          {selectedProcessesItem.map((process) => (
            <Box
              key={process.id}
              maxWidth="550px"
              borderWidth="thin"
              borderRadius="medium"
              borderColor="surface.border.gray.muted"
              padding="spacing.5"
            >
              <Box
                display="flex"
                justifyContent="space-between"
                alignItems="center"
                gap="spacing.2"
                width="100%"
              >
                <Box display="flex" flexDirection="column" gap="spacing.2">
                  <Text size="medium" weight="semibold" color="surface.text.gray.normal">
                    {process.name || ''}
                  </Text>
                  <Text size="small" color="surface.text.gray.subtle">
                    Select the sources data you want
                  </Text>
                </Box>
                <Button
                  icon={CloseIcon}
                  accessibilityLabel="Close"
                  size="medium"
                  variant="tertiary"
                  onClick={() => removeProcessCardHandler({ processId: process.id })}
                />
              </Box>
              <Divider variant="muted" marginY="spacing.4" />
              <Box>
                <Text>
                  {process.merchant_sources.length
                    ? process.merchant_sources.map((type) => type.name).join(', ')
                    : null}
                </Text>
              </Box>
            </Box>
          ))}
        </Box>
      )}
      <Box display="flex" justifyContent="flex-end">
        <Button
          isDisabled={selectedProcessesItem.length === 0}
          variant="primary"
          onClick={(e) => {
            e.preventDefault();
            setHasCompletedCreateReportStep((prevStep) => ({
              ...prevStep,
              sourceData: true,
            }));
          }}
        >
          Confirm
        </Button>
      </Box>
    </Box>
  );
};

export default ConfigProcessSelection;
