/* eslint-disable consistent-return */
import React, { useState } from 'react';
import {
  Box,
  RadioGroup,
  Radio,
  FileUpload,
  TextInput,
  Text,
  ImageIcon,
} from '@razorpay/blade/components';
// import { SketchPicker } from 'react-color';
import { StyledPopupOverlay } from '../../styled';

import { FILE_UPLOAD_OPTIONS } from 'merchant/views/GCMS/Programs/CreateProgram/constants';

export default function GiftCardDesign({ values, errors, onChange }) {
  const [colorPickerVisible, setColorPickerVisible] = useState(false);
  const [color, setColor] = useState('#000000');

  function changeColor(hex) {
    setColor(hex);
    onChange('brand_color', hex);
  }
  return (
    <Box>
      <Box display="flex" flexDirection="row" gap="spacing.6">
        <Box display="flex" flexDirection="column" gap="spacing.6">
          <Box
            borderRadius="large"
            padding="spacing.5"
            borderColor="surface.border.gray.muted"
            width="450px"
          >
            <Box>
              <RadioGroup
                label="Choose Gift Card Design"
                necessityIndicator="required"
                isRequired={true}
                size="medium"
                labelPosition="top"
                name="upload_type"
                marginBottom={'spacing.4'}
                onChange={({ name, value }) => {
                  onChange('url', null);
                  onChange('image', null);
                  onChange('brand_color', '#000');
                  setColor('#000000');
                  onChange(name, value);
                }}
                value={values.upload_type}
              >
                {[FILE_UPLOAD_OPTIONS.BRAND_DESIGN, FILE_UPLOAD_OPTIONS.CUSTOM].map((option) => (
                  <Radio value={option.value} helpText={option.helpText}>
                    {option.title}
                  </Radio>
                ))}
              </RadioGroup>
            </Box>
          </Box>
          {values.upload_type && (
            <Box
              borderRadius="large"
              padding="spacing.5"
              borderColor="surface.border.gray.muted"
              width="450px"
              position="relative"
            >
              <FileUpload
                accept={
                  values.upload_type === FILE_UPLOAD_OPTIONS.CUSTOM.value
                    ? '.jpg, .jpeg, .png'
                    : '.png, .svg'
                }
                fileList={values.image ? [values.image] : []}
                helpText={
                  values.upload_type === FILE_UPLOAD_OPTIONS.CUSTOM.value
                    ? '180px(width) x 118px(height). Only JPG or PNG file'
                    : 'Only SVG or PNG file.'
                }
                isRequired
                label={
                  values.upload_type === FILE_UPLOAD_OPTIONS.BRAND_DESIGN.value
                    ? 'Logo'
                    : 'Custom Image'
                }
                maxCount={1}
                maxSize={2097152}
                name="image"
                necessityIndicator="required"
                onChange={({ name, fileList }) => {
                  onChange('url', null);
                  onChange(name, fileList[0]);
                }}
                onRemove={() => {
                  onChange('url', null);
                  onChange('image', undefined);
                }}
                uploadType="single"
              />
              {values.upload_type === FILE_UPLOAD_OPTIONS.BRAND_DESIGN.value && (
                <Box gap="spacing.4" marginTop="spacing.3">
                  <TextInput
                    name="brand_color"
                    value={values.brand_color}
                    label="Brand Color"
                    onChange={() => {}}
                    onClick={() => setColorPickerVisible(true)}
                  />
                  {/* {colorPickerVisible && (
                    <>
                      <StyledPopupOverlay onClick={() => setColorPickerVisible(false)} />
                      <SketchPicker
                        onChangeComplete={({ hex }) => changeColor(hex)}
                        color={values.brand_color}
                      />
                    </>
                  )} */}
                </Box>
              )}
            </Box>
          )}
        </Box>
        <Box
          borderRadius="large"
          padding="spacing.7"
          flexDirection="column"
          borderColor="surface.border.gray.muted"
          display="flex"
          height="fit-content"
        >
          <Text size="large" weight="semibold">
            Preview
          </Text>
          <Box
            borderRadius="large"
            borderColor="surface.border.gray.muted"
            overflow="hidden"
            display="flex"
            width="350px"
            height="222px"
            alignItems="center"
            marginTop="12px"
            justifyContent="center"
          >
            {!(values.image || values.url) && <ImageIcon size="2xlarge" />}
            {!values.url &&
              values.image &&
              values.upload_type === FILE_UPLOAD_OPTIONS.CUSTOM.value && (
                <img
                  src={URL.createObjectURL(values.image)}
                  width="100%"
                  height="100%"
                  style={{ 'object-fit': 'contain' }}
                />
              )}
            {!values.url &&
              values.image &&
              values.upload_type === FILE_UPLOAD_OPTIONS.BRAND_DESIGN.value && (
                <Box
                  backgroundColor={color}
                  display="flex"
                  width="100%"
                  height="100%"
                  justifyContent="center"
                  alignItems="center"
                >
                  <img
                    src={URL.createObjectURL(values.image)}
                    width="100%"
                    height="100%"
                    style={{ 'object-fit': 'contain' }}
                  />
                </Box>
              )}
            {values.url && (
              <img
                src={values.url}
                width="100%"
                height="100%"
                style={{ 'object-fit': 'contain' }}
              />
            )}
          </Box>
        </Box>
      </Box>
    </Box>
  );
}
