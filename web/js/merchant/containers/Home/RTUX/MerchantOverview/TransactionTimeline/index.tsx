import React, { Suspense, useEffect } from 'react';
import { connect } from 'react-redux';
import User from 'merchant/models/User';
import { updateSession as updateSessionReducer } from 'merchant/reducers/session';
import { User as UserType } from 'common/typings';
import { Box, Text, Heading } from '@razorpay/blade/components';
import lazyLoader from 'merchant/routes/LazyLoader';
import { isMobileDevice } from '@libs/shared-utils';
import { IMerchantPayments, ISettlementData } from '../types';
import TrackTransactionsSkeleton from './TrackTransactionsSkeleton';
import { bindActionCreators } from 'redux';
import { analyticsTrackWithUserInfo } from '@libs/shared-utils';

const FTUXTransactionsComplete = lazyLoader(
  () =>
    import(
      /* webpackChunkName: "FTUXTransactionsComplete" */ '@federated/apps/onboarding-experience/components/FTUXTransactionsComplete'
    ),
);
const FTUXTransactionsTimeline = lazyLoader(
  () =>
    import(
      /* webpackChunkName: "FTUXTransactionsTimeline" */ '@federated/apps/onboarding-experience/components/FTUXTransactionsTimeline'
    ),
);

const TransactionTimeline = ({
  transactions,
  settlementData,
  isLoading,
  updateSession,
  user,
}: {
  transactions: IMerchantPayments[];
  settlementData?: ISettlementData;
  isLoading: boolean;
  updateSession: (args: { user: UserType; mode?: string }) => void;
  user: UserType;
}) => {
  const username = user.user?.name || user.name;
  const isMobile = isMobileDevice();
  const transactionsCount = transactions.length;

  const moveToSettlementsView = () => {
    const newUser = new User({ ...user, show_transaction_timeline: false });
    updateSession({
      user: newUser as unknown as UserType,
    });
  };

  useEffect(() => {
    analyticsTrackWithUserInfo({
      objectName: 'FTUX Transaction Timeline Step',
      actionName: 'Loaded',
      screen: 'home page',
      properties: {
        transactionsCount,
      },
    });
  }, []);

  return (
    <Box marginX={{ base: 'spacing.0', m: 'spacing.6' }}>
      <Box
        display="flex"
        flexDirection="column"
        gap="spacing.2"
        alignItems="center"
        paddingBottom={{ base: 'spacing.8', m: 'spacing.7' }}
      >
        <Heading
          color="surface.text.gray.normal"
          size={isMobile ? 'large' : 'xlarge'}
          weight="semibold"
        >
          Hey {username}
        </Heading>
        <Text
          color="surface.text.gray.subtle"
          size={isMobile ? 'small' : 'medium'}
          weight="regular"
          textAlign="center"
        >
          Let's start your Razorpay journey and track your first 5 transactions
        </Text>
      </Box>

      <Box elevation="lowRaised" borderRadius="medium">
        {isLoading ? (
          <TrackTransactionsSkeleton />
        ) : transactionsCount >= 5 ? (
          <Suspense fallback={<TrackTransactionsSkeleton />}>
            <FTUXTransactionsComplete moveToSettlementsView={moveToSettlementsView} />
          </Suspense>
        ) : (
          <Suspense fallback={<TrackTransactionsSkeleton />}>
            <FTUXTransactionsTimeline transactions={transactions} settlementData={settlementData} />
          </Suspense>
        )}
      </Box>
    </Box>
  );
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators({ updateSession: updateSessionReducer }, dispatch);

export default connect(
  (state) => ({
    user: state.session.user,
  }),
  mapDispatchToProps,
)(TransactionTimeline);
