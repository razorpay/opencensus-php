import React, { useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { Box, Button, Heading, Text, TextArea, TextInput } from '@razorpay/blade/components';
import { editStorefront } from 'merchant/reducers/paymentPages/storefront';
import PaymentPagesDrawer from 'merchant/views/PaymentPages/common/Drawer';
import { validateContactDetails } from 'merchant/views/PaymentPages/common/Products/utils';
import BusinessDetailsMobile from './BusinessDetailsMobile';

interface IBusinessDetailsDrawer {
  handleClose: () => void;
  entity: {
    contactEmail: string;
    contactPhone: string;
    terms?: string;
    title: string;
  };
  isMobile: boolean;
  openBuisnessDetailsDrawer: boolean;
  editStorefront: (name, value) => void;
}

interface IFormDetails {
  entity: {
    contactEmail: string;
    contactPhone: string;
    terms?: string;
    title: string;
  };
  state: any;
  handleChange: (e: any) => void;
  errors: any;
  textAreaHeight?: 2 | 3 | 4 | 5 | undefined;
}

export const FormDetails = ({
  state,
  entity,
  errors,
  handleChange,
  textAreaHeight = 2,
}: IFormDetails): React.ReactElement => {
  const { contactEmail, contactPhone, terms } = state;

  return (
    <Box>
      <TextInput
        label="Support email id"
        labelPosition="top"
        name="contactEmail"
        necessityIndicator="required"
        onChange={handleChange}
        value={contactEmail}
        placeholder="Enter your email address"
        validationState={errors.contactEmail ? 'error' : 'none'}
        isRequired
        errorText={errors.contactEmail}
      />
      <Box
        borderColor="surface.border.gray.muted"
        borderWidth="thin"
        borderStyle="dashed"
        marginY="spacing.7"
      ></Box>
      <TextInput
        label="Support phone number"
        labelPosition="top"
        name="contactPhone"
        necessityIndicator="required"
        onChange={handleChange}
        value={contactPhone}
        placeholder="Enter your phone number"
        validationState={errors.contactPhone ? 'error' : 'none'}
        isRequired
        errorText={errors.contactPhone}
      />
      <Box
        borderColor="surface.border.gray.muted"
        borderWidth="thin"
        borderStyle="dashed"
        marginY="spacing.7"
      ></Box>
      <TextArea
        label="Write your terms and condition "
        labelPosition="top"
        name="terms"
        numberOfLines={2}
        placeholder="Enter here"
        validationState="none"
        onChange={handleChange}
        value={terms}
      />
      <Text
        variant="caption"
        size="medium"
        color="surface.text.gray.muted"
        margin={['spacing.3', 'spacing.0', 'spacing.5']}
      >
        This is an optional field.
      </Text>
      <TextArea
        label="Mandatory policy information"
        labelPosition="top"
        name="mandatoryterms"
        numberOfLines={textAreaHeight}
        placeholder={`You agree to share information entered on this page with ${entity?.title} (owner of this page) and Razorpay, adhering to applicable laws.`}
        validationState="none"
        isDisabled={true}
      />
      <Text variant="caption" size="medium" color="surface.text.gray.muted" marginTop="spacing.3">
        You can’t edit this information.
      </Text>
    </Box>
  );
};

const BusinessDetailsDrawer = ({
  handleClose,
  openBuisnessDetailsDrawer,
  entity,
  isMobile,
  editStorefront,
}: IBusinessDetailsDrawer): React.ReactElement => {
  const [state, setState] = useState({
    contactEmail: entity?.contactEmail || '',
    contactPhone: entity?.contactPhone || '',
    terms: entity?.terms || '',
  });

  const [errors, setErrors] = useState({
    contactEmail: '',
    contactPhone: '',
  });

  const { contactEmail, contactPhone, terms } = state;

  const handleChange = (e) => {
    setState((prevState) => ({
      ...prevState,
      [e.name]: e.value,
    }));

    setErrors({
      ...errors,
      [e.name]: '',
    });
  };

  const onSubmit = () => {
    const { errors, isValid } = validateContactDetails(state);

    if (!isValid) {
      setErrors(errors);
      return;
    }

    const updates = [
      { field: 'contactPhone', value: contactPhone },
      { field: 'contactEmail', value: contactEmail },
      { field: 'terms', value: terms },
    ];

    updates.forEach(({ field, value }) => {
      if (value !== entity?.[field]) {
        editStorefront(field, value);
      }
    });

    handleClose();
  };

  const footerButtons = [
    <Button size="medium" type="button" variant="secondary" key="cancel" onClick={handleClose}>
      Cancel
    </Button>,
    <Button
      size="medium"
      type="button"
      variant="primary"
      key="submit"
      onClick={onSubmit}
      isDisabled={!(contactPhone && contactEmail)}
    >
      Save
    </Button>,
  ];

  return isMobile ? (
    <BusinessDetailsMobile
      handleClose={handleClose}
      openBuisnessDetailsDrawer={openBuisnessDetailsDrawer}
      handleChange={handleChange}
      onSubmit={onSubmit}
      formState={state}
      errors={errors}
    />
  ) : (
    <PaymentPagesDrawer maskClosable={false} onClose={handleClose} footerButtons={footerButtons}>
      <Heading size="medium" weight="semibold" color="surface.text.gray.normal">
        Add your business details
      </Heading>
      <Box display="flex" alignItems="center" gap="spacing.1" marginBottom="spacing.5">
        <Text color="interactive.text.notice.normal" variant="body" size="small" weight="regular">
          *
        </Text>
        <Text
          color="surface.text.gray.muted"
          variant="body"
          size="small"
          weight="regular"
          marginBottom="spacing.3"
        >
          Indicates required information
        </Text>
      </Box>
      <FormDetails
        state={state}
        entity={entity}
        handleChange={handleChange}
        errors={errors}
        textAreaHeight={3}
      />
    </PaymentPagesDrawer>
  );
};

const mapStateToProps = (state) => ({
  entity: state.paymentPageStorefront.entity,
  isMobile: state.app.isMobileResolution,
});

const mapDispatchToProps = (dispatch) => ({
  editStorefront: bindActionCreators(editStorefront, dispatch),
});

export default connect(mapStateToProps, mapDispatchToProps)(BusinessDetailsDrawer);
