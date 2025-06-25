import React from 'react';
import { RowWrapper } from './styled';
import { Box, Text, Spinner } from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';
import { getMerchantPaymentDetails } from './utils';
import { Link } from 'react-router-dom';

const UnregisteredWebsiteError: React.FC<{
  label: string;
  paymentId: string;
}> = ({ label, paymentId }) => {
  const { data: merchantPaymentDetails, isLoading: isMerchantPaymentDetailsLoading } = useQuery({
    queryKey: ['merchantPaymentDetails', paymentId],
    queryFn: () =>
      getMerchantPaymentDetails({
        payment_id: paymentId.replace('pay_', ''),
      }),
    enabled: !!paymentId,
  });

  const { referer } = merchantPaymentDetails || {};
  return (
    <RowWrapper>
      <Text variant="body" size="medium" weight="regular" color="surface.text.gray.subtle">
        {label}
      </Text>
      {isMerchantPaymentDetailsLoading ? (
        <Box display="flex" justifyContent="center" alignItems="center">
          <Spinner accessibilityLabel="loading payment details" size="medium" />
        </Box>
      ) : (
        <Box>
          <Text color="surface.text.gray.normal">Unregistered Website</Text>
          {!!referer && (
            <Link to={referer} target="_blank">
              <Text color="surface.text.primary.normal" wordBreak="break-all" as="span">
                {referer}
              </Text>
            </Link>
          )}
        </Box>
      )}
    </RowWrapper>
  );
};

export default UnregisteredWebsiteError;
