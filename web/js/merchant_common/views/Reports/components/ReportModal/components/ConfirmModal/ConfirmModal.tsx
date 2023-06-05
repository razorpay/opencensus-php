import React, { useState } from 'react';
import { Alert, Box, Button, Heading, Text } from 'merchant_common/views/Reports/components';
import { ConfirmModalProps } from './types';

export const ConfirmModal = ({ params, onClose }: ConfirmModalProps): JSX.Element => {
  const [isLoading, setLoading] = useState(false);

  if (!params?.modalConfig) return <></>;

  const {
    modalConfig: {
      title,
      desc,
      confirmBtn: { onClick, icon, label },
      alert: { intent, description },
    },
  } = params;

  return (
    <Box padding="spacing.8">
      <Heading variant="regular">{title}</Heading>
      <Text variant="body" size="medium" weight="regular" color="surface.text.subdued.lowContrast">
        {desc}
      </Text>

      <Alert
        title="Important"
        description={description}
        marginTop="spacing.4"
        intent={intent}
        isDismissible={false}
      />

      <Box marginTop="spacing.4" display="flex" justifyContent="flex-end">
        <Button accessibilityLabel="Cancel" size="medium" onClick={onClose} variant="tertiary">
          Cancel
        </Button>

        <Button
          isLoading={isLoading}
          size="medium"
          onClick={() => onClick({ setLoading, closeModal: onClose })}
          accessibilityLabel={label}
          marginLeft="spacing.4"
          iconPosition="left"
          icon={icon}
        >
          {label}
        </Button>
      </Box>
    </Box>
  );
};
