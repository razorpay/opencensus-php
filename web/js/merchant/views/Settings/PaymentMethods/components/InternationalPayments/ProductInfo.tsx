import React from 'react';
import InternationalStatusLabel from 'merchant/components/InternationalStatusLabel';
import Amount from 'common/ui/Amount';
import { STATUS_MAP } from 'merchant/views/Settings/PaymentMethods/components/InternationalPayments/constants';
import type { ProductInfoProps } from 'merchant/views/Settings/PaymentMethods/components/InternationalPayments/types';
import { Text, Box, Link } from '@razorpay/blade/components';

const ProductInfo = ({
  title,
  status,
  product,
  transactionSize,
  settlementCycle,
  showStatusLabel,
  showRequestAccessBtn,
  questionnaireStatus,
  onRequestAccessClick,
}: ProductInfoProps): JSX.Element | null => {
  let description: JSX.Element | string | null;
  switch (status) {
    case 'rejected':
      description =
        'Currently we do not support international payments for these products. Please reach out to support for any queries';
      break;
    case 'in_review':
      description =
        'Request has been submitted. We are verifying your request. This would take roughly 3-5 days.';
      break;
    case 'no_action_received':
      description = `Raise a request to activate international card payments on ${
        product === 'pg' ? 'payment gateway' : 'other products'
      }`;
      break;
    case 'approved':
      description = (
        <>
          <Text>
            Transaction Size Enabled :{' '}
            <Text as="span" weight="bold">
              <Amount value={transactionSize} currency="INR" />
            </Text>
          </Text>
          <Text>
            Settlement Cycle :&nbsp;
            <Text as="span" weight="bold">
              T+{settlementCycle}
            </Text>
          </Text>
        </>
      );
      break;
    default:
      return null;
  }
  return (
    <div className="product-info">
      <Box display="flex" justifyContent="space-between" marginBottom="spacing.4">
        <Text as="span" weight="bold">
          {title}
        </Text>

        {showRequestAccessBtn ? (
          <Link variant="button" marginLeft="spacing.2" onClick={onRequestAccessClick}>
            {questionnaireStatus?.new_flow &&
            questionnaireStatus.enablement_progress === 'in_progress'
              ? `Edit draft (${questionnaireStatus.percentage_completion}%)`
              : 'Request Access'}
          </Link>
        ) : (
          showStatusLabel && <InternationalStatusLabel status={STATUS_MAP[status]} />
        )}
      </Box>
      {description}
    </div>
  );
};

export default ProductInfo;
