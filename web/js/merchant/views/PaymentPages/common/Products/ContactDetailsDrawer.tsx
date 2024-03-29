import React, { useState } from 'react';
import { Button, Text, TextInput } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { editStorefront } from 'merchant/reducers/paymentPages/storefront';
import PaymentPagesDrawer from 'merchant/views/PaymentPages/common/Drawer';

import { SubHeading } from './styled';
import { validateContactDetails } from './utils';

interface IContactDetailsDrawer {
  handleClose: () => void;
  entity: {
    contactEmail: string;
    contactPhone: string;
  };
  editStorefront: (name, value) => void;
}

const ContactDetailsDrawer = ({
  handleClose,
  entity,
  editStorefront,
}: IContactDetailsDrawer): React.ReactElement => {
  const [state, setState] = useState({
    contactEmail: entity?.contactEmail || '',
    contactPhone: entity?.contactPhone || '',
  });

  const [errors, setErrors] = useState({
    contactEmail: '',
    contactPhone: '',
  });

  const { contactEmail, contactPhone } = state;

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

    editStorefront('contactPhone', contactPhone);
    editStorefront('contactEmail', contactEmail);

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
      Save contact details
    </Button>,
  ];
  return (
    <PaymentPagesDrawer maskClosable={false} onClose={handleClose} footerButtons={footerButtons}>
      <Text size="large">Contact details</Text>
      <SubHeading>Provide your contact details for customers to contact you</SubHeading>
      <TextInput
        label="Business email id"
        labelPosition="top"
        name="contactEmail"
        necessityIndicator="required"
        onChange={handleChange}
        value={contactEmail}
        placeholder="Enter Business email id"
        validationState={errors.contactEmail ? 'error' : 'none'}
        isRequired
        errorText={errors.contactEmail}
      />
      <br />
      <TextInput
        label="Business phone no."
        labelPosition="top"
        name="contactPhone"
        necessityIndicator="required"
        onChange={handleChange}
        value={contactPhone}
        placeholder="Enter Business phone no."
        validationState={errors.contactPhone ? 'error' : 'none'}
        isRequired
        errorText={errors.contactPhone}
      />
    </PaymentPagesDrawer>
  );
};

const mapStateToProps = (state) => ({
  entity: state.paymentPageStorefront.entity,
});

const mapDispatchToProps = (dispatch) => ({
  editStorefront: bindActionCreators(editStorefront, dispatch),
});

export default connect(mapStateToProps, mapDispatchToProps)(ContactDetailsDrawer);
