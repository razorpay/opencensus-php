import React from 'react';

import { Text, TextInput } from '@razorpay/blade/components';

import { MessageTextInputProps } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/types/titleType';

const MessageTextInput: React.FC<MessageTextInputProps> = ({
  message,
  placeholder = 'Add message on checkout',
  value,
  onChange,
}) => {
  return (
    <div>
      <Text
        weight="semibold"
        size="small"
        color="surface.text.gray.muted"
        marginBottom={'spacing.2'}
      >
        {message}
      </Text>
      <TextInput
        name="bannerMessageText"
        label=""
        placeholder={placeholder}
        value={value}
        onChange={onChange}
      />
    </div>
  );
};

export default MessageTextInput;
