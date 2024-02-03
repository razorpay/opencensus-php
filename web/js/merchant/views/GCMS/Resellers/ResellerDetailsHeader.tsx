import React from 'react';
import { Amount, Box, Heading, Skeleton, Text } from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';
import styled from 'styled-components';

import { fetchResellerBalance } from './queries';

const StyledImg = styled.img`
  height: 60px;
  width: 60px;
  border-radius: 5px;
`;

const ResellerDetailsHeader = ({
  merchantId,
  resellerId,
}: {
  merchantId: string;
  resellerId: string;
}) => {
  const {
    isLoading,
    data: resellerBalance,
    isError,
  } = useQuery({
    queryKey: ['reseller:balance', merchantId, resellerId],
    queryFn: () => fetchResellerBalance({ merchantId, resellerId }),
  });

  return (
    <Box
      backgroundColor="surface.background.level3.lowContrast"
      width="100%"
      display="flex"
      padding="spacing.6"
      borderRadius="medium"
      justifyContent="space-between"
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
          <Box display="flex">
            <StyledImg src={resellerBalance.logo} />
            <Box
              display="flex"
              flexDirection="column"
              justifyContent="space-between"
              marginLeft="spacing.4"
            >
              <Heading>{resellerBalance.merchant_name}</Heading>
              <Box display="flex">
                <Text color="surface.text.muted.lowContrast">ID: </Text>
                <Text weight="bold" color="surface.text.muted.lowContrast">
                  {resellerBalance.merchant_id}
                </Text>
              </Box>
            </Box>
          </Box>
          <Box>
            <Text size="medium" color="surface.text.muted.lowContrast">
              Virtual Account Balance
            </Text>
            <Amount isAffixSubtle={false} value={resellerBalance.balance} size="body-medium-bold" />
          </Box>
        </>
      )}
    </Box>
  );
};

export default ResellerDetailsHeader;
