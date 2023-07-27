import React from 'react';
import InternationalStatusLabel from 'merchant/components/InternationalStatusLabel';
import ActivationStatus from 'merchant/views/Settings/PaymentMethods/components/InternationalPayments/ActivationStatus';
import {
  STATUS_MAP,
  ENABLEMENT_STATUS,
} from 'merchant/views/Settings/PaymentMethods/components/InternationalPayments/constants';
import { InternationalPaymentsProps } from 'merchant/views/Settings/PaymentMethods/components/InternationalPayments/types';
import { Button, Box, Text } from '@razorpay/blade/components';

const InternationalPayments = ({
  config: {
    isKycComplete,
    isWebsiteAdded,
    questionnaireStatus,
    currentStatusOnHeader,
    isRequestAccessAllowed,
    isInternationalBlackList,
  },
  onRequestAccessClick,
}: InternationalPaymentsProps): JSX.Element => {
  if (!isWebsiteAdded || isInternationalBlackList) {
    return (
      <li className="international-leaf-item alert-info">
        <Box>
          <Box>
            <Text as="span" weight="bold">
              International Payments
            </Text>
            {isInternationalBlackList && (
              <Text>International payments is not supported for your business type</Text>
            )}
            {!isWebsiteAdded && (
              <Text>Please update your website to request for international payments</Text>
            )}
          </Box>
        </Box>
      </li>
    );
  }

  const status = STATUS_MAP[currentStatusOnHeader];

  return (
    <li className="international-leaf-item">
      <Box>
        <Box>
          <Text as="span" weight="bold">
            International Payments
          </Text>
          <ActivationStatus status={status} />
        </Box>
        {isRequestAccessAllowed ? (
          <Button
            variant="primary"
            marginLeft="spacing.2"
            onClick={onRequestAccessClick}
            isDisabled={!isKycComplete}
          >
            {questionnaireStatus?.new_flow &&
            questionnaireStatus?.enablement_progress === ENABLEMENT_STATUS.in_progress
              ? `Edit draft (${questionnaireStatus?.percentage_completion}%)`
              : 'Request'}
          </Button>
        ) : null}
        {currentStatusOnHeader ? <InternationalStatusLabel status={status} /> : null}
      </Box>
    </li>
  );
};

export default InternationalPayments;
