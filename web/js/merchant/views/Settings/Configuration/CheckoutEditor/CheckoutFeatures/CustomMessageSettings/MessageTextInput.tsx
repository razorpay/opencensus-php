import React from 'react';
import { Box, Text, TextInput } from '@razorpay/blade/components';
import { MessageTextInputProps } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/types/titleType';

const MessageTextInput: React.FC<MessageTextInputProps> = ({
  message,
  placeholder = 'Add message on checkout',
  value,
  onChange,
}) => {
  return (
    <Box>
      <Text weight="semibold" size="small" color="surface.text.gray.muted" marginBottom="spacing.2">
        {message}
      </Text>
      <TextInput
        name="bannerMessageText"
        label=""
        placeholder={placeholder}
        value={value}
        onChange={onChange}
      />
    </Box>
  );
};

export default MessageTextInput;
