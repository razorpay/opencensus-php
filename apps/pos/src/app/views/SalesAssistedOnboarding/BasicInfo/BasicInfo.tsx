import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { TextInput, Button, Box, Text } from '@razorpay/blade/components';
import { useStore } from 'shell/commonStore';
import { saveUserName } from './api';
import { RazorpayLogoBlue } from 'apps/pos/src/assets';

const BasicInfo = (): JSX.Element => {
  const [userName, setUserName] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const navigate = useNavigate();
  const showNotification = useStore((state) => state.showNotification);

  const handleUserNameChange = ({ value = '' }: { value?: string }) => {
    setUserName(value);
  };

  const handleSubmit = async () => {
    setIsLoading(true);
    try {
      const payload = {
        name: userName,
      };
      const response = await saveUserName(payload);
      if (response?.success) {
        showNotification({ type: 'success', message: 'Updated Name Successfully!' });
        navigate('/pos-sales/join');
      } else {
        throw new Error();
      }
    } catch (err: { errors: Array<string> } | any) {
      showNotification({ type: 'error', message: err?.errors ?? 'Failed to update user name!' });
      setIsLoading(false);
    }
  };

  return (
    <Box
      margin={['spacing.3', 'spacing.8']}
      display="flex"
      flexDirection="column"
      justifyContent="space-between"
      height="80vh"
    >
      <Box
        display="flex"
        flexDirection="column"
        justifyContent="center"
        flex="1"
        paddingY="spacing.5"
        gap="spacing.4"
      >
        <img src={RazorpayLogoBlue} alt="Razorpay" height="32px" width="32px" />
        <TextInput
          value={userName}
          onChange={handleUserNameChange}
          placeholder="Enter Your name"
          label=""
          size="medium"
          marginTop="spacing.3"
        />
        <Text variant="caption" size="medium" color="interactive.text.neutral.muted">
          Please check your name, the name should match your government ID card
        </Text>
      </Box>
      <Box>
        <Button
          variant="primary"
          color="primary"
          size="medium"
          isFullWidth
          onClick={handleSubmit}
          type="button"
          isDisabled={userName === ''}
          isLoading={isLoading}
        >
          Save & Continue
        </Button>
      </Box>
    </Box>
  );
};

export default BasicInfo;
