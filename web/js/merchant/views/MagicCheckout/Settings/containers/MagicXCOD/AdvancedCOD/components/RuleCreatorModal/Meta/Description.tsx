import React, { useState } from 'react';
import { Box, Text, TextInput, Link, EditIcon } from '@razorpay/blade/components';

export const Description = ({ value, onChange }) => {
  const [isEditing, setIsEditing] = useState(false);
  const conditionalProps: any = isEditing ? { flexGrow: '1', minWidth: '348px' } : {};

  return (
    <Box display="flex" {...conditionalProps}>
      {isEditing && (
        <Box
          display="flex"
          flexDirection="column"
          gap="spacing.3"
          minHeight="58px"
          {...conditionalProps}
        >
          <Text weight="semibold" size="small" color="surface.text.gray.muted">
            Description
          </Text>

          <TextInput
            accessibilityLabel="Edit rule description"
            value={value}
            onChange={(e) => {
              onChange(e.value);
            }}
          />
        </Box>
      )}
      {!isEditing && (
        <Link
          variant="button"
          icon={EditIcon}
          accessibilityLabel="Edit rule description"
          onClick={() => {
            setIsEditing(true);
          }}
          alignSelf="flex-end"
        >
          Edit Description
        </Link>
      )}
    </Box>
  );
};
