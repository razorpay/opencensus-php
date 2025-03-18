import React, { useState } from 'react';
import {
  ArrowRightIcon,
  Box,
  Button,
  Link,
  Tag,
  Text,
  TextInput,
} from '@razorpay/blade/components';
import { isEmpty } from 'lodash';
import _track from './track';

export default function BinNumber({ updatedCardModalConfig, setUpdatedCardModalConfig, org }) {
  const [singleBINNumber, setSingleBINNumber] = useState<string>('');
  const [validationError, setValidationError] = useState<string | undefined>();

  const handleAddBIN = () => {
    if (singleBINNumber.length === 6) {
      setUpdatedCardModalConfig((prev) => ({
        ...prev,
        iins: isEmpty(prev.iins) ? [singleBINNumber] : [...prev.iins, singleBINNumber],
      }));
      setSingleBINNumber('');
      setValidationError(undefined);
      _track.cardBinNumberAdded(singleBINNumber);
    } else {
      setValidationError('Bin number should be 6 digits');
    }
  };

  const handleRemoveBIN = (values: string[]) => {
    setUpdatedCardModalConfig((prev) => ({
      ...prev,
      iins: prev.iins.filter((item) => !values.includes(item)),
    }));
  };

  return (
    <Box
      backgroundColor="surface.background.gray.moderate"
      paddingY="spacing.5"
      paddingX="spacing.4"
      borderRadius="large"
    >
      <Text weight="semibold" size="large">
        BIN Number
      </Text>
      <Link target="_blank" href={`https://${org.business_name?.toLowerCase() || "razorpay"}.com/docs/payments/payment-gateway/web-integration/standard/configure-payment-methods/supported-methods/#supported-cards`} display="flex" icon={ArrowRightIcon} iconPosition="right">
        See documentation
      </Link>
      <Box gap="spacing.5" display="flex" flexDirection="column" marginTop="spacing.5">
        <Box display="flex" gap="spacing.5">
          <TextInput
            placeholder="Enter BIN number"
            label=""
            maxCharacters={6}
            type="number"
            validationState={validationError ? 'error' : 'none'}
            errorText={validationError}
            onChange={({ value }) => {
              const isValidNumber = /^\d+$/.test(value || '');
              if (isValidNumber) {
                setSingleBINNumber(value || '');
                setValidationError(undefined);
              } else {
                setValidationError('Bin number should be digits only');
              }
            }}
            value={singleBINNumber}
          />
          <Box>
            <Button onClick={handleAddBIN}>Add</Button>
          </Box>
        </Box>

        {updatedCardModalConfig.iins?.length > 0 && (
          <Box display="flex" gap="spacing.1">
            {updatedCardModalConfig.iins.map((item, index) => (
              <Tag onDismiss={() => handleRemoveBIN(item)} key={index}>
                {item}
              </Tag>
            ))}
          </Box>
        )}
      </Box>
    </Box>
  );
}
