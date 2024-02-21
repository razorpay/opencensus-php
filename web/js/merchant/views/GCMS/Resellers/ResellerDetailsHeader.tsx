import React from 'react';
import { Box, Button, Heading, Skeleton, Text } from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';

import { ModeT } from 'common/services/mode';
import { getFormattedAmountNew } from 'common/utils/rzp-utils';

import { fetchResellerBalance } from './queries';
const ResellerDetailsHeader = ({
  mode,
  merchantId,
  resellerId,
  hasOrderCreate = false,
  cartLength,
  isLoadingViewCart,
  onClickOrderCreate,
  onClickViewCart,
}: {
  mode: ModeT;
  merchantId: string;
  resellerId: string;
  hasOrderCreate?: boolean;
  cartLength?: number;
  isLoadingViewCart?: boolean;
  onClickOrderCreate?: () => void;
  onClickViewCart?: () => void;
}): JSX.Element => {
  const {
    isLoading,
    data: resellerBalance,
    isError,
  } = useQuery({
    queryKey: ['reseller:balance', merchantId, resellerId, mode],
    queryFn: () => fetchResellerBalance({ merchantId, resellerId, mode }),
  });

  return (
    <Box
      backgroundColor="surface.background.level2.lowContrast"
      width="100%"
      display="flex"
      padding="spacing.6"
      borderRadius="medium"
    >
      <Box
        flex={1}
        backgroundColor="surface.background.level2.lowContrast"
        display="flex"
        justifyContent="space-between"
        flexWrap="wrap"
      >
        {isLoading || isError ? (
          <>
            <Box display="flex">
              <Skeleton width="60px" height="60px" borderRadius="small" />
              <Box
                display="flex"
                flexDirection="column"
                justifyContent="space-between"
                marginLeft="spacing.4"
              >
                <Skeleton width="100px" height="20px" />
                <Box display="flex">
                  <Text color="surface.text.muted.lowContrast">ID: </Text>
                  <Skeleton width="100px" height="20px" marginLeft="spacing.2" />
                </Box>
              </Box>
            </Box>
            <Box>
              <Text size="medium" color="surface.text.muted.lowContrast">
                Virtual Account Balance
              </Text>
              <Skeleton width="100px" height="20px" marginTop="spacing.2" />
            </Box>
          </>
        ) : (
          <>
            <Box display="flex" paddingRight="spacing.4">
              <Box
                display="flex"
                flexDirection="column"
                justifyContent="space-between"
                marginLeft="spacing.2"
              >
                <Heading>{resellerBalance?.merchant_name}</Heading>
                <Box display="flex">
                  <Text color="surface.text.muted.lowContrast">ID: </Text>
                  <Text weight="bold" color="surface.text.muted.lowContrast">
                    {resellerBalance?.merchant_id}
                  </Text>
                </Box>
              </Box>
            </Box>
            <Box>
              <Text size="medium" color="surface.text.muted.lowContrast">
                Virtual Account Balance
              </Text>
              <Text color="surface.text.subtle.lowContrast" weight="bold" size="large">
                {getFormattedAmountNew(resellerBalance?.balance ?? 0, true)}
              </Text>
            </Box>
          </>
        )}
      </Box>
      {hasOrderCreate && (
        <Box display="flex" flexDirection="row" alignItems="flex-end" paddingLeft="spacing.6">
          <Box padding="spacing.2">
            <Button variant="primary" onClick={onClickOrderCreate}>
              Create Order
            </Button>
          </Box>
          <Box padding="spacing.2">
            <Button
              isDisabled={!cartLength}
              isLoading={isLoadingViewCart}
              variant="secondary"
              onClick={onClickViewCart}
            >
              {`View Cart (${cartLength || 0})`}
            </Button>
          </Box>
        </Box>
      )}
    </Box>
  );
};

export default ResellerDetailsHeader;
