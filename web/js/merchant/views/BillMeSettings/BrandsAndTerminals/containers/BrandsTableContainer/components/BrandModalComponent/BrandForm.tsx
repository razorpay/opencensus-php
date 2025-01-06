import React, { useState } from 'react';
import { Box, TextInput, TextArea, FileUpload } from '@razorpay/blade/components';

import type { BrandPayloadType } from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/BrandsTableContainer/types';

type BrandFormProps = {
  brandPayload: BrandPayloadType;
  updateBrandPayload: <K extends keyof BrandPayloadType>(
    key: K,
    value: BrandPayloadType[K],
  ) => void;
};

type FieldValidationInfo = { showError: boolean; errorText: string };

const BrandForm = ({ brandPayload, updateBrandPayload }: BrandFormProps): React.ReactElement => {
  const [fieldValidationInfo, setFieldValidationInfo] = useState<FieldValidationInfo>({
    showError: false,
    errorText: '',
  });
  const { name, description, logo } = brandPayload;

  const handleNameChange = (name: string) => {
    if (!name.length) {
      setFieldValidationInfo({
        showError: true,
        errorText: 'Name is a mandatory field',
      });
    } else if (name.length > 50) {
      setFieldValidationInfo({
        showError: true,
        errorText: 'Brand name cannot be more than 50 characters',
      });
    } else if (fieldValidationInfo.showError && name.length && name.length <= 50) {
      setFieldValidationInfo({ showError: false, errorText: '' });
    }
    updateBrandPayload('name', name);
  };

  return (
    <Box
      paddingX="spacing.6"
      paddingY="spacing.5"
      display="flex"
      flexDirection="column"
      gap="spacing.7"
    >
      <TextInput
        isRequired
        validationState={fieldValidationInfo.showError ? 'error' : 'none'}
        errorText={fieldValidationInfo.errorText}
        label="Name"
        placeholder="Enter Brand Name"
        necessityIndicator="required"
        value={name}
        onChange={({ value }) => handleNameChange(value || '')}
      />
      <TextArea
        label="Description"
        placeholder="Enter Description"
        value={description || ''}
        onChange={({ value }) => updateBrandPayload('description', value || '')}
        maxCharacters={200}
      />
      <FileUpload
        accept=".jpg, .jpeg, .png"
        uploadType="single"
        label="Image"
        helpText="Recommended Size = 128px x 128px. Max Size = 5MB"
        maxSize={5242880}
        onChange={({ fileList }) => updateBrandPayload('logo', fileList[0])}
        onDrop={({ fileList }) => updateBrandPayload('logo', fileList[0])}
        onRemove={() => updateBrandPayload('logo', null)}
        fileList={logo ? [logo] : []}
      />
    </Box>
  );
};

export default BrandForm;
