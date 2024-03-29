import React, { useContext } from 'react';
import { Box, Text } from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';

import { Error } from 'common/new-ui/Input';
import Spinner from 'common/ui/Spinner';
import { GCMSSession, SessionContext } from 'merchant/views/GCMS/shared/context';
import { MerchantReseller } from 'merchant/views/GCMS/shared/types';

import { GCMSOrderSession, OrderSessionContext } from './context';
import { fetchMerchantResellerRelationshipDetails } from './queries';

const OrderCartBillingSection = () => {
  const { mode, merchantId } = useContext<GCMSSession>(SessionContext);
  const { resellerId } = useContext<GCMSOrderSession>(OrderSessionContext);

  const {
    isLoading: isLoadingMerchantResellerRelationshipDetails,
    data: merchantResellerRelationshipDetails,
    isError: isErrorMerchantResellerRelationshipDetails,
    error: errorMerchantResellerRelationshipDetails,
  } = useQuery<MerchantReseller, Error>({
    queryKey: ['merchant:reseller', merchantId, resellerId, mode],
    queryFn: () => fetchMerchantResellerRelationshipDetails({ resellerId, merchantId, mode }),
  });

  return (
    <Box padding={['spacing.2', 'spacing.0', 'spacing.4', 'spacing.0']}>
      <Box paddingBottom="spacing.4">
        <Text color="surface.text.gray.subtle" size="large">
          Billing Details
        </Text>
      </Box>
      <div className="content">
        {isLoadingMerchantResellerRelationshipDetails ? (
          <div className="page-spinner-container">
            <Spinner center={undefined} />
          </div>
        ) : (
          <Box>
            <Box padding={['spacing.6']}>
              <Text color="surface.text.gray.muted">
                {merchantResellerRelationshipDetails?.billing_detail?.business_name}
              </Text>
              <Text color="surface.text.gray.muted">
                {merchantResellerRelationshipDetails?.region}
              </Text>
              <Text color="surface.text.gray.muted">
                {`GSTIN: ${merchantResellerRelationshipDetails?.billing_detail?.gst_number}`}
              </Text>
            </Box>
            {isErrorMerchantResellerRelationshipDetails && (
              <Box>
                <Error
                  text={errorMerchantResellerRelationshipDetails?.message || 'Something Went Wrong'}
                />
              </Box>
            )}
          </Box>
        )}
      </div>
    </Box>
  );
};

export default OrderCartBillingSection;
