import React, { useState } from 'react';
import {
  Box,
  Text,
  LockIcon,
  Badge,
  TextInput,
  Button,
  FileUpload,
} from '@razorpay/blade/components';

import { fetchInvoice } from './services';
import { convertToFile, getOnboardingDetails, isMerchantFullyOnboarded } from './utils';
import { FetchInvoiceType } from './types';

const FetchInvoice = ({ file, onUpload, showNotification, onRemove }: FetchInvoiceType) => {
  /*
   * Get partner status from context here and pass it in the below function
   */
  const isOnboardingComplete = isMerchantFullyOnboarded([]);
  const { headerText, buttonText, partner, status } = getOnboardingDetails([]);

  const isAutoFetchEnabled = false;

  const [irn, setIrn] = useState('');
  const [isLoading, setIsLoading] = useState(false);

  const onFetchInvoice = async () => {
    try {
      if (irn.length && !isLoading) {
        setIsLoading(true);
        const fileData = await fetchInvoice(irn);
        const file = convertToFile(fileData, irn);
        onUpload(file);
      }
    } catch (error: any) {
      showNotification({
        type: 'error',
        message: error?.errors?.join(' ') || 'Something went wrong',
      });
    } finally {
      setIsLoading(false);
    }
  };

  const handleIrnChange = (data) => {
    setIrn(data.value);
  };

  const onOnboardingButtonClicked = () => {
    /* TODO :open model with the partner and current status */
    console.log(partner, status);
  };

  return (
    <Box
      display="flex"
      flexDirection="column"
      padding={isOnboardingComplete ? 'spacing.0' : 'spacing.7'}
      borderRadius="medium"
      backgroundColor={isOnboardingComplete ? 'transparent' : 'surface.background.gray.subtle'}
    >
      {!isOnboardingComplete && (
        <Box
          display="flex"
          flexDirection="row"
          alignItems="center"
          paddingBottom="spacing.4"
          borderBottomColor="surface.border.gray.muted"
          borderBottomWidth="thinner"
        >
          <LockIcon size="large" marginRight="spacing.3" color="surface.icon.gray.normal" />
          <Text weight="semibold" color="surface.text.gray.normal">
            {headerText}
          </Text>
        </Box>
      )}
      <Box
        display="flex"
        flexDirection="column"
        paddingTop="spacing.4"
        paddingBottom="spacing.4"
        gap="spacing.7"
      >
        <Box display="flex" flexDirection="column">
          <Box
            display="flex"
            flexDirection="row"
            justifyContent="space-between"
            marginBottom="spacing.4"
          >
            <Text weight="semibold" display={{ base: 'none', m: 'flex' }}>
              Invoice reference number
            </Text>
            <Text weight="semibold" display={{ base: 'flex', m: 'none' }}>
              Enter IRN
            </Text>
            {!isOnboardingComplete && <Badge color="notice">SETUP REQUIRED</Badge>}
          </Box>
          <Box
            display="flex"
            flexDirection="row"
            justifyContent="space-between"
            alignItems="center"
            gap="spacing.4"
          >
            <Box flex="1">
              <TextInput
                isDisabled={!isOnboardingComplete}
                placeholder="1XTRm9065S"
                label=""
                onChange={handleIrnChange}
              />
            </Box>
            <Button isDisabled={!irn.length} onClick={onFetchInvoice} isLoading={isLoading}>
              Fetch invoice
            </Button>
          </Box>
          {file && (
            <Box marginTop="spacing.5">
              <FileUpload
                uploadType="single"
                fileList={file ? [file] : undefined}
                label=""
                accept=".jpg, .jpeg, .pdf"
                maxSize={200000}
                onChange={({ fileList }) => {
                  onUpload(fileList[0]);
                }}
                onDrop={({ fileList }) => {
                  onUpload(fileList[0]);
                }}
                onRemove={onRemove}
              />
              <Text marginTop="spacing.3" color="interactive.text.positive.normal">
                Invoice fetched successfully!
              </Text>
            </Box>
          )}
        </Box>
        {isAutoFetchEnabled && (
          <Box display="flex" flexDirection="column">
            <Box
              display="flex"
              flexDirection="row"
              justifyContent="space-between"
              marginBottom="spacing.4"
            >
              <Text>Auto-fetch invoices</Text>
              <Badge color="notice">SETUP REQUIRED</Badge>
            </Box>
            <Box>
              <Text color="surface.text.gray.disabled" size="small">
                Hassle free auto-fetching of e-invoices from GST/NIC portal{' '}
              </Text>
            </Box>
          </Box>
        )}
      </Box>
      {!isOnboardingComplete && (
        <Box
          display="flex"
          justifyContent="flex-end"
          paddingTop="spacing.4"
          borderTopColor="surface.border.gray.muted"
          borderTopWidth="thinner"
        >
          <Button onClick={onOnboardingButtonClicked}>{buttonText}</Button>
        </Box>
      )}
    </Box>
  );
};

export default FetchInvoice;
