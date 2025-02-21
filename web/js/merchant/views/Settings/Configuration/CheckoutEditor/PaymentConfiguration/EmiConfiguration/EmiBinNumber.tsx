import React from 'react';
import { Box, Button, Tag, TextInput } from '@razorpay/blade/components';
import { isEmpty } from 'lodash';

export default function EmiBinNumber({ finalCardConfigurationObj, setFinalCardConfigurationObj }) {
  const [singleBINNumber, setSingleBINNumber] = React.useState<string>('');
  const [validationError, setValidationError] = React.useState<string | undefined>();

  const handleAddBIN = () => {
    if (singleBINNumber.length === 6) {
      setFinalCardConfigurationObj((prev) => ({
        ...prev,
        iins: isEmpty(prev.iins) ? [singleBINNumber] : [...prev.iins, singleBINNumber],
      }));
      setSingleBINNumber('');
      setValidationError(undefined);
    } else {
      setValidationError('Bin number should be 6 digits');
    }
  };

  const handleRemoveBIN = (values: string[]) => {
    setFinalCardConfigurationObj((prev) => ({
      ...prev,
      iins: prev.iins.filter((item) => !values.includes(item)),
    }));
  };

  return (
    <Box>
      <Box gap="spacing.5" display="flex" flexDirection="column" marginTop={'spacing.5'}>
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

        {finalCardConfigurationObj?.iins?.length > 0 && (
          <Box display="flex" gap="spacing.1">
            {finalCardConfigurationObj.iins.map((item, index) => (
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
