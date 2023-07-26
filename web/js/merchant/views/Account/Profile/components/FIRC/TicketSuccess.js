import React from 'react';

// Components
import { Button, Box, Heading, Text } from '@razorpay/blade/components';
///- Components

/**
 * Render a success message for the ticket update request.
 *
 * @param {Object} props - Callback function to close the ticket success component.
 * @param {() => void} props.onClose - Callback function to close the ticket success component.
 * @return {JSX.Element} - The JSX element representing the ticket success component.
 */
const TicketSuccess = ({ onClose }) => {
  return (
    <div className="purpose-code-container">
      <div className="confirmation-content">
        <Box textAlign="center" marginY="spacing.7">
          <img
            width="72"
            height="72"
            src="https://cdn.razorpay.com/static/assets/ticket-system/icon-green-tick.svg"
            alt="success"
          />
        </Box>
        <Heading size="large" textAlign="center" marginBottom="spacing.8">
          Purpose code update request has been sent!
        </Heading>
        <Text contrast="low" textAlign="center" marginBottom="spacing.6">
          Your new purpose code will be reviewed by our banking partner before it is approved.
        </Text>
        <Text contrast="low" type="muted" textAlign="center" marginBottom="spacing.6">
          Purpose code changes are reflected on the dashboard usually within 48 hours.
        </Text>
      </div>

      <Box
        display="flex"
        gap="1rem"
        paddingX="1.5rem"
        paddingBottom="spacing.6"
        className="footer-section"
      >
        <Button onClick={onClose} isFullWidth>
          Close
        </Button>
      </Box>
    </div>
  );
};

export default React.memo(TicketSuccess);
