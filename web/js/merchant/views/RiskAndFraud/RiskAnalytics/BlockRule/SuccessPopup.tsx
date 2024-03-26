import React from 'react';
import {
  Button,
  Link,
  Modal,
  ModalBody,
  ModalFooter,
  ModalHeader,
  Text,
} from '@razorpay/blade/components';
import { useNavigate } from 'react-router-dom';

import { SUPPORT_TICKETS_URL } from './constants';
import { SuccessPopupProps } from './types';

const SuccessPopup = ({ isOpen = false, ticketId, onDismiss }: SuccessPopupProps) => {
  const navigate = useNavigate();

  const onTicketPageClick = () => {
    navigate(SUPPORT_TICKETS_URL);
  };

  return (
    <Modal isOpen={isOpen} onDismiss={onDismiss} zIndex={1000}>
      <ModalHeader title="Request sent successfully" />
      <ModalBody>
        <Text>
          Your request to blacklist has been sent successfully (Ticket ID: {ticketId})! You would
          receive an email once our team has actioned upon it.
        </Text>
        <Text>
          Meanwhile, you can track your request on{' '}
          <Link onClick={onTicketPageClick}>tickets page</Link>.
        </Text>
      </ModalBody>
      <ModalFooter>
        <Button marginLeft="auto" onClick={onDismiss}>
          Got it
        </Button>
      </ModalFooter>
    </Modal>
  );
};

export default SuccessPopup;
