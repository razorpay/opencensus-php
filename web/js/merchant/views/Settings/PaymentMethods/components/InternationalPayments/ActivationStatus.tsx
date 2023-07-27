import React from 'react';
import {
  STATUS_MAP,
  RAZORPAY_SUPPORT_LINK,
} from 'merchant/views/Settings/PaymentMethods/components/InternationalPayments/constants';
import { ActivationStatusProps } from 'merchant/views/Settings/PaymentMethods/components/InternationalPayments/types';
import { Text, Link } from '@razorpay/blade/components';

/**
 * Renders the activation status message based on the provided status.
 *
 * @param {string} status - The status of the activation request.
 * @return {JSX.Element} The activation status message component.
 */
const ActivationStatus = ({ status }: ActivationStatusProps): JSX.Element => {
  switch (status) {
    case STATUS_MAP.in_review: {
      return (
        <Text>
          Your request to enable international payments has been received by us and will be
          processed in 3-5 business days. For any queries, please reach out to{' '}
          <Link target="_blank" href={RAZORPAY_SUPPORT_LINK} rel="noreferrer noopener">
            support
          </Link>
          .
        </Text>
      );
    }

    case STATUS_MAP.rejected: {
      return (
        <Text>
          Your request to enable international payments was rejected. Please check your Email, SMS
          or Whatsapp to know more. For any queries, please reach out to{' '}
          <Link target="_blank" href={RAZORPAY_SUPPORT_LINK} rel="noreferrer noopener">
            support
          </Link>
          .
        </Text>
      );
    }

    case STATUS_MAP.no_action_received: {
      return (
        <Text>
          Your request couldn&apos;t be approved. For any queries, reach out to{' '}
          <Link target="_blank" href={RAZORPAY_SUPPORT_LINK} rel="noreferrer noopener">
            support
          </Link>
          .
        </Text>
      );
    }

    default: {
      return (
        <Text>
          Enable international payments: Cards, Bank transfers (ACH, SEPA, CHAPS, SWIFT) and local
          payment methods (Trustly, Giropay, SofortPay)
        </Text>
      );
    }
  }
};

export default React.memo(ActivationStatus);
