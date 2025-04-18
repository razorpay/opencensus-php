import React from 'react';
import {
  CheckIcon,
  Box,
  Button,
  IconButton,
  TextInput,
  Text,
  EditIcon,
} from '@razorpay/blade/components';

const ProcessNameEditor = ({
  isEditingProcessName,
  processName,
  aiIngestionStages,
  setProcessName,
  setIsEditingProcessName,
}) => {
  if (isEditingProcessName) {
    return (
      <Box
        display="grid"
        gridTemplateColumns="1.7fr 0.3fr"
        gap="spacing.7"
        justifyContent="start"
        alignItems="center"
        paddingY="spacing.6"
      >
        <TextInput
          label="Name for the process"
          helpText="You can change it later in the reconciliation process options"
          placeholder="Enter the name of the process"
          size="medium"
          value={processName}
          isRequired={true}
          necessityIndicator="required"
          onChange={({ value }) => {
            setProcessName(value || '');
          }}
        />
        <Button
          color="primary"
          variant="secondary"
          size="medium"
          marginTop="spacing.2"
          isFullWidth={true}
          icon={CheckIcon}
          iconPosition="right"
          onClick={() => setIsEditingProcessName(false)}
        >
          Save
        </Button>
      </Box>
    );
  }

  return (
    <Box display="flex" flexDirection="column" gap="spacing.3" paddingY="spacing.6">
      <Text weight="semibold" color="surface.text.gray.normal">
        Name for the process
      </Text>
      <Box display="flex" flexDirection="row" gap="spacing.5">
        <Text weight="medium" color="surface.text.gray.subtle">
          {processName || 'Untitled Reconciliation'}
        </Text>
        {!aiIngestionStages['Add Sources'] ? (
          <IconButton
            icon={EditIcon}
            size="medium"
            onClick={() => setIsEditingProcessName(true)}
            accessibilityLabel="Edit"
          />
        ) : null}
      </Box>
    </Box>
  );
};

export default ProcessNameEditor;
