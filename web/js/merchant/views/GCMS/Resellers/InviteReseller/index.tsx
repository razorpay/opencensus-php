import React, { useState } from 'react';
import {
  Box,
  Text,
  TextInput,
  PhoneNumberInput,
  Button,
  Link,
  TrashIcon,
  useToast,
  Heading,
} from '@razorpay/blade/components';
import { withFormik } from 'formik';
import { useMutation } from '@tanstack/react-query';
import { validateResellerName, validateResellerEmail, validateResellerPhone } from './validators';

import { inviteReseller } from '../queries';

function InviteReseller({
  touched,
  values,
  errors,
  setFieldTouched,
  setFieldValue,
  setRefetchQuery,
  closeModal,
  refetchResellers,
}) {
  const {
    mutateAsync: inviteResellerMutation,
    isLoading,
    error,
    isError,
    reset,
  } = useMutation({
    mutationFn: inviteReseller,
    onSuccess: () => {
      show({
        color: 'positive',
        type: 'informational',
        content: 'Reseller created successfully!',
      });
      refetchResellers();
      closeModal();
    },
    onError: () => {
      show({
        color: 'negative',
        type: 'informational',
        content: 'Failed to create reseller!',
      });
    },
  });
  const { show } = useToast();

  function handleFormChange(name, value) {
    if (!touched[name]) {
      setFieldTouched(name);
    }
    setFieldValue(name, value);
  }

  async function handleSubmit() {
    const { phone, name, email } = values;
    try {
      await inviteResellerMutation({ name, phone, email });
    } catch (err) {}
  }

  function isValid() {
    return !Object.values(errors).some((err) => !!err);
  }

  errors.name = validateResellerName(values.name);
  errors.email = validateResellerEmail(values.email);
  errors.phone = validateResellerPhone(values.phone);
  return (
    <Box
      zIndex="99999"
      width="100vw"
      height="100vh"
      top="spacing.0"
      left="spacing.0"
      position="fixed"
      backgroundColor="surface.background.gray.intense"
      display="flex"
      flexDirection="column"
      justifyContent="center"
      alignItems="center"
    >
      <Box position="absolute" top="30px" right="50px" zIndex="10000">
        <Link onClick={closeModal} icon={TrashIcon} size="small">
          Discard
        </Link>
      </Box>
      <Box
        width="350px"
        display="flex"
        height="100%"
        justifyContent="center"
        flexDirection="column"
        flexGrow="1"
      >
        <Box
          display="flex"
          flexDirection="column"
          justifyContent="space-between"
          marginBottom="spacing.5"
          gap="spacing.2"
        >
          <Heading weight="regular" size="large">
            <Heading weight="semibold" display="inline-flex" size="large">
              Create
            </Heading>{' '}
            Reseller
          </Heading>
          <Text size="small" color="interactive.text.gray.muted">
            Provide essential details to onboard a reseller
          </Text>
        </Box>
        <Box
          display="flex"
          flexDirection="column"
          gap="spacing.4"
          borderRadius="large"
          borderColor="surface.border.gray.muted"
          padding="spacing.4"
        >
          <TextInput
            isRequired
            necessityIndicator="required"
            autoFocus
            label="Reseller Name"
            labelPosition="top"
            name="name"
            placeholder="Enter your reseller name"
            maxCharacters={50}
            onChange={({ name, value }) => {
              handleFormChange(name, value);
            }}
            value={values.name}
            validationState={touched.name && errors.name ? 'error' : 'none'}
            errorText={errors?.name}
            testID="reseller-name"
          />
          <TextInput
            isRequired
            necessityIndicator="required"
            type="email"
            label="Reseller Email Id"
            labelPosition="top"
            name="email"
            placeholder="Enter email to send invite link"
            onChange={({ name, value }) => {
              handleFormChange(name, value);
            }}
            value={values.email}
            validationState={touched.email && errors.email ? 'error' : 'none'}
            errorText={errors?.email}
            testID="reseller-email"
          />
          {/* NOTE: Country selector drastically increase input lag */}
          <PhoneNumberInput
            defaultCountry="IN"
            label="Reseller Phone"
            onChange={({ name, value }) => handleFormChange(name, value)}
            showCountrySelector={false}
            placeholder="0000000000"
            name="phone"
            marginTop="spacing.4"
            value={values.phone}
            validationState={touched.phone && errors.phone ? 'error' : 'none'}
            errorText={errors?.phone}
            size="medium"
            testID="phone-number"
            onClearButtonClick={() => handleFormChange('phone', '')}
          />
        </Box>
      </Box>
      <Box
        width="100%"
        display="flex"
        borderTopColor="surface.border.gray.muted"
        padding="spacing.4"
        justifyContent="flex-end"
      >
        <Button onClick={handleSubmit} isDisabled={isLoading || !isValid()} isLoading={isLoading}>
          Create Reseller
        </Button>
      </Box>
    </Box>
  );
}

export default withFormik({
  mapPropsToValues: () => ({}),
  validate: () => {
    const errors = {};
    return errors;
  },
  validateOnChange: true,
})(InviteReseller);
