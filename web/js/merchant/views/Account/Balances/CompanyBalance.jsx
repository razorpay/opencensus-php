import { Box, Text, Amount, Skeleton, Link, RefreshIcon } from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';
import { graphqlRequest } from '@federated/apps/shell/graphql';
import { BILL_ME_COMPANY_BALANCE_QUERY } from './queries';

export const CompanyBalance = () => {
  const {
    data: companyBalance,
    isFetching,
    isError,
    refetch,
  } = useQuery({
    queryKey: ['billMeCompanyBalance'],
    queryFn: () =>
      graphqlRequest({
        document: BILL_ME_COMPANY_BALANCE_QUERY,
      }),
    retry: false,
  });

  return (
    <Box
      display="flex"
      flexDirection="column"
      gap="spacing.3"
      elevation="midRaised"
      padding="spacing.6"
      borderRadius="medium"
      backgroundColor="surface.background.gray.intense"
    >
      <Text size="large" weight="semibold" color="interactive.text.gray.subtle">
        Wallet Balance (BillMe)
      </Text>
      <Text size="medium">
        Enjoy uninterrupted messaging services on digital billing & campaign management with your
        wallet balance
      </Text>
      {isFetching ? (
        <Skeleton height="spacing.5" width="30%" borderRadius="medium" />
      ) : isError ? (
        <Box display="flex" justifyContent="center" gap="spacing.3">
          <Text size="medium" color="interactive.text.negative.normal">
            Error loading balance
          </Text>
          <Link
            size="medium"
            variant="button"
            icon={RefreshIcon}
            iconPosition="right"
            onClick={refetch}
          >
            Retry
          </Link>
        </Box>
      ) : (
        <Amount
          type="heading"
          size="large"
          value={
            (companyBalance.billWalletBalance.balance,
            {
              currency: 'INR',
            })
          }
          currency="INR"
          color={
            companyBalance.billWalletBalance.balance < 0
              ? 'interactive.text.negative.normal'
              : undefined
          }
        />
      )}
    </Box>
  );
};
