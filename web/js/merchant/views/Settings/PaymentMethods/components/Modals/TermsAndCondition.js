import React from 'react';
import {
  Box,
  Text,
  List,
  Modal,
  Button,
  ListItem,
  ModalBody,
  ModalHeader,
  ModalFooter,
  Alert,
  Link,
} from '@razorpay/blade/components';
import { getWebsiteDetailsInfo } from 'merchant/views/Settings/Configuration/Questionnaire/utils';
import { readableURL } from 'merchant/views/Settings/PaymentMethods/components/ScrapperModal/utils';

const TermsAndCondition = ({ isOpen, handleClose, props, handleAcceptTermsAndConditions }) => {
  const getModalTitle = () => {
    return `Requirements for enabling ${props.instrument.name}`;
  };
  const merchantWebsiteDetails = getWebsiteDetailsInfo(props.user);

  return (
    <Modal isOpen={isOpen} onDismiss={() => handleClose(false)} size="medium">
      <ModalHeader
        title={getModalTitle()}
        subtitle="We will forward the activation request to the banking partners. You'll receive a response within 2-3 business days"
      />
      <ModalBody>
        <Box display="flex" flexDirection="column" gap="spacing.9">
          <Box display="flex" flexDirection="column" gap="spacing.7">
            <Text variant="body" weight="regular">
              To ensure payment providers approve your request for{' '}
              {merchantWebsiteDetails.isWebsiteDetails ? (
                <Link target="_blank" href={merchantWebsiteDetails.websitesData[0]}>
                  {readableURL(merchantWebsiteDetails.websitesData[0])}
                </Link>
              ) : (
                'your website'
              )}
              , please verify you have the following pages on your website:
            </Text>
            <List size="medium">
              <ListItem>Terms and Conditions</ListItem>
              <ListItem>Privacy Policy</ListItem>
              <ListItem>Cancellation and Refund</ListItem>
              <ListItem>Shipping and Exchange</ListItem>
              <ListItem>Contact Us</ListItem>
            </List>
          </Box>
          <Alert
            isDismissible={false}
            isFullWidth
            color="notice"
            description="Missing pages or incomplete implementation as per suggested guidelines will result in activation request denial"
          />
        </Box>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
          <Button variant="tertiary" onClick={() => handleClose(false)}>
            No, I don't have these
          </Button>
          <Button onClick={handleAcceptTermsAndConditions}>I understand, proceed</Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

export default TermsAndCondition;
