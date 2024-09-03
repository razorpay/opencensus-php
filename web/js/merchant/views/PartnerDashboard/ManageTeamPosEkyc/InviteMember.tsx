import React, { useState } from 'react';
import {
  Box,
  Chip,
  ChipGroup,
  Heading,
  Modal,
  ModalBody,
  Text,
  TextInput,
  Button,
  SendIcon,
  IconButton,
  CloseIcon,
  TextInputProps,
  ChipGroupProps,
} from '@razorpay/blade/components';
import { FormikProps } from 'formik';

import { InviteMemberDataT } from './types';

interface InviteMemberProps {
  isOpen: boolean;
  closeModal: () => void;
  formik: FormikProps<InviteMemberDataT>;
}

const InviteMember = ({ isOpen = false, closeModal, formik }: InviteMemberProps): JSX.Element => {
  const [backgroundImage, setBackgroundImage] = useState('initial-url');
  const isEditMode = !!formik.values.id; //if id has been set, edit this member details

  //TODO: check edit content with design & update to support i18n
  //TODO: in figma mobile version has been used for typography. to check with design
  const title = isEditMode ? 'Edit Details' : 'Invite a Member';
  const subtitle = isEditMode
    ? 'Edit employee details and update'
    : 'Enter employee details and create link to invite them';
  const submitButtonLabel = isEditMode ? 'Update Details' : 'Send Invite';
  const memberNameLabel = isEditMode ? 'Member Name' : 'Enter Name of New Member';

  const onRoleChange: ChipGroupProps['onChange'] = ({ values }) => {
    formik.setFieldValue('role', values[0]);
    setBackgroundImage('get-image-for-role');
  };

  const onInputChange: TextInputProps['onChange'] = ({ name, value }) => {
    formik.setFieldValue(name as string, value);
  };

  return (
    <Modal isOpen={isOpen} onDismiss={closeModal} size="medium">
      <ModalBody padding="spacing.0">
        <Box
          display="grid"
          gridTemplateColumns="repeat(2, 1fr)"
          gap="64px"
          paddingX="40px"
          paddingY="32px"
          minHeight="550px"
        >
          <Box display="flex" flexDirection="column">
            <Heading size="xlarge">{title}</Heading>
            <Text size="medium" color="surface.text.gray.muted" marginTop="spacing.3">
              {subtitle}
            </Text>
            <form
              onSubmit={(e) => {
                e.preventDefault();
                formik.submitForm();
              }}
              style={{
                display: 'flex',
                flexDirection: 'column',
                justifyContent: 'space-between',
                flexGrow: '1',
              }}
            >
              <Box marginTop="spacing.6">
                {/* TODO: replace below text with chipGroup label prop once blade has been updated */}
                <Text
                  size="small"
                  weight="semibold"
                  color="surface.text.gray.muted"
                  marginBottom="spacing.4"
                >
                  Select Role
                </Text>
                <ChipGroup
                  accessibilityLabel="Select Role"
                  defaultValue="partner_agent"
                  selectionType="single"
                  onChange={onRoleChange}
                >
                  <Chip value="partner_agent">Agent</Chip>
                </ChipGroup>
                <TextInput
                  name="name"
                  label={memberNameLabel}
                  value={formik.values.name}
                  onChange={onInputChange}
                  isRequired={true}
                  marginTop="spacing.6"
                  errorText={formik.errors.name}
                  validationState={formik.errors.name ? 'error' : 'none'}
                />
                <TextInput
                  name="contactMobile"
                  label="Enter Phone Number"
                  value={formik.values.contactMobile}
                  onChange={onInputChange}
                  isRequired={true}
                  marginTop="spacing.6"
                  helpText="Active phone number of the employee"
                  isDisabled={isEditMode}
                  errorText={formik.errors.contactMobile}
                  validationState={formik.errors.contactMobile ? 'error' : 'none'}
                />
                {/* TODO: Assign team lead to be added in v2 */}
              </Box>
              <Button
                isDisabled={formik.isSubmitting || !formik.isValid}
                isLoading={formik.isSubmitting}
                type="submit"
                icon={SendIcon}
                iconPosition="right"
              >
                {submitButtonLabel}
              </Button>
            </form>
          </Box>

          {/* TODO: get background image here */}
          <Box
            height="100%"
            width="100%"
            backgroundColor="surface.background.cloud.subtle"
            backgroundImage={backgroundImage}
            borderRadius="2xlarge"
          />
        </Box>
        <Box position="absolute" top="spacing.6" right="spacing.6">
          <IconButton icon={CloseIcon} onClick={closeModal} accessibilityLabel="Close Modal" />
        </Box>
      </ModalBody>
    </Modal>
  );
};

export default InviteMember;
