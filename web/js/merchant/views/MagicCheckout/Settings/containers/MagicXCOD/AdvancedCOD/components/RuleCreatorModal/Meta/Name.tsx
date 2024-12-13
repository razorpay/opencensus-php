import React, { useState } from 'react';
import { Box, Text, TextInput, Link, EditIcon } from '@razorpay/blade/components';

export const Name = ({ value, onChange }) => {
  const [isEditing, setIsEditing] = useState(false);
  const [hasError, setHasError] = useState(false);
  const conditionalProps: any = isEditing ? { flexGrow: '1', minWidth: '348px' } : {};

  return (
    <Box display="flex" gap="spacing.10">
      <Box
        display="flex"
        flexDirection="column"
        justifyContent="space-between"
        minHeight="58px"
        {...conditionalProps}
      >
        <Text weight="semibold" size="small" color="surface.text.gray.muted">
          Rule Name
        </Text>
        {!isEditing ? (
          <Text weight="medium" color="surface.text.gray.subtle">
            {value}
          </Text>
        ) : (
          <TextInput
            accessibilityLabel="Edit rule name"
            size="medium"
            value={value}
            validationState={hasError ? 'error' : 'none'}
            onChange={(e) => {
              const val = e.value;
              onChange(val);
              setHasError(!Boolean(val?.trim()));
            }}
          />
        )}
      </Box>
      {!isEditing && (
        <Link
          variant="button"
          icon={EditIcon}
          accessibilityLabel="Edit rule name"
          onClick={() => {
            setIsEditing(true);
          }}
          alignSelf="flex-end"
        >
          Edit Name
        </Link>
      )}
    </Box>
  );
};
