// core
import React from 'react';
///- core

// Formik Components
import { Formik, Form } from 'formik';
///- Formik Components

// components
import { Button, Box, Spinner, Link, ExternalLinkIcon, Heading } from '@razorpay/blade/components';
import { Modal, ModalContent } from 'common/new-ui/Modal';
import { ModalAsideNav } from 'common/new-ui/Wizard';
import BuyerAddressForm from 'merchant/views/Transactions/v1/B2bPayments/components/BuyerAddressModal/BuyerAddressForm';
///- components

// styled
import {
  FormHeader,
  SupportWarning,
} from 'merchant/views/Transactions/v1/B2bPayments/components/BuyerAddressModal/styled';
///- styled

// constants
import { BUYER_ADDRESS_VALIDATION_SCHEMA } from 'merchant/views/Transactions/v1/B2bPayments/components/BuyerAddressModal/constants';
///- constants

// hooks
import { useBuyerAddressState } from 'merchant/views/Transactions/v1/B2bPayments/components/BuyerAddressModal/hooks';
///- hooks

// types
import { BuyerAddressModalProps } from 'merchant/views/Transactions/v1/B2bPayments/components/BuyerAddressModal/types';
///- types

const BuyerAddressModal = ({
  paymentId,
  showNotification,
  onClose,
}: BuyerAddressModalProps): JSX.Element => {
  const {
    states,
    countries,
    buyerAddress,
    isStatesLoading,
    isAddressSaving,
    isAddressLoading,
    isAddressAlreadyExists,
    onCountrySelect,
    onSaveAddress,
  } = useBuyerAddressState({ onClose, showNotification, paymentId });

  return (
    <Modal onClose={onClose} className="intl-questionnaire">
      <ModalContent>
        <div className="Wizard">
          <ModalAsideNav
            title="Add Buyer's Address Details"
            description={
              <p>
                Fill the address details of your buyer as per your invoice copy raised to initiate
                the settlement
              </p>
            }
            tabs={['Address Details']}
            activeTab={0}
          />

          <Formik
            enableReinitialize
            initialValues={buyerAddress}
            validationSchema={BUYER_ADDRESS_VALIDATION_SCHEMA}
            onSubmit={onSaveAddress}
          >
            {({ values, errors, touched, isSubmitting, isValid, handleBlur, handleChange }) => (
              <Form data-testid="buyer-address-form">
                <main className="form-container">
                  <FormHeader>
                    <Heading size="small" color="surface.text.gray.normal">
                      ADDRESS DETAILS
                    </Heading>
                  </FormHeader>
                  {isAddressLoading ? (
                    <Box
                      minHeight="150px"
                      display="flex"
                      justifyContent="center"
                      alignItems="center"
                    >
                      <Spinner
                        size="xlarge"
                        accessibilityLabel="Checking address"
                        label="Checking address"
                      />
                    </Box>
                  ) : (
                    <BuyerAddressForm
                      values={values}
                      errors={errors}
                      touched={touched}
                      states={states}
                      countries={countries}
                      isStatesLoading={isStatesLoading}
                      isAddressLoading={isAddressLoading}
                      isAddressAlreadyExists={isAddressAlreadyExists}
                      onChange={handleChange}
                      onBlur={handleBlur}
                      onCountrySelect={onCountrySelect}
                    />
                  )}
                  {isAddressAlreadyExists && (
                    <SupportWarning>
                      If you want to edit buyer&apos;s address details, please reach out to{' '}
                      <Link
                        rel="noopener noreferrer"
                        target="_blank"
                        href="https://razorpay.com/support"
                        icon={ExternalLinkIcon}
                        iconPosition="right"
                      >
                        Razorpay Support
                      </Link>
                    </SupportWarning>
                  )}
                </main>
                <footer>
                  {isAddressAlreadyExists ? (
                    <Button variant="primary" type="button" onClick={onClose}>
                      Close
                    </Button>
                  ) : (
                    <Button
                      variant="primary"
                      type="submit"
                      isDisabled={!isValid}
                      isLoading={isSubmitting || isAddressSaving}
                    >
                      Submit
                    </Button>
                  )}
                </footer>
              </Form>
            )}
          </Formik>
        </div>
      </ModalContent>
    </Modal>
  );
};

export default BuyerAddressModal;
