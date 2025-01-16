import React, { useState } from 'react';
import { connect } from 'react-redux';
import SpinnerLegacy from 'common/ui/Spinner';
import Popover, { PopoverBody } from 'common/ui/Popover';
import rolesList from 'merchant/helpers/permissions/roles-list';
import {
  Amount,
  Box,
  Button,
  Card,
  CardBody,
  CardHeader,
  CardHeaderLeading,
  Spinner,
  Text,
} from '@razorpay/blade/components';

import { TICKET_STATUS } from '../constants';
import { i18nifyConvertToMajorUnit } from 'merchant/views/Transactions/v2/common/utils';
import { fetchReserveBalance } from 'merchant/reducers/profile';
import { bindActionCreators } from 'redux';
import WithdrawButton from 'merchant/views/Account/components/WithdrawModal/WithdrawButton';
import { useStore } from 'shell/commonStore';

const getReserveBalanceAmount = (items, reserveBalanceError) => {
  if (!items) return 0;

  if (items.length === 0 || reserveBalanceError === true) {
    return 0;
  } else {
    return items[0].balance;
  }
};

function ReserveBalance({
  ticketStatus,
  handleContactUs,
  reserveBalance,
  user,
  handlAddFunds,
  ticketGenerated,
  handleActivate,
  fetchReserveBal,
  mode,
}) {
  const items = reserveBalance.data?.items;
  const { showNotification } = useStore();
  const balance = getReserveBalanceAmount(items, reserveBalance.error);
  const { data: ticketStatusData, loading: ticketStatusLoading } = ticketStatus;

  const [resBalanceLoading, setResBalanceLoading] = useState(false);

  const fetchReserveBalanceAfterWithdrawal = () => {
    setResBalanceLoading(true);
    fetchReserveBal().then((response) => {
      if (response.success) {
        setResBalanceLoading(false);
      } else {
        setResBalanceLoading(false);
        showNotification({
          type: 'error',
          message: 'Failed to fetch Reserve Balance, please refresh the page',
        });
      }
    });
  };

  if (ticketStatusLoading) {
    return (
      <div class="page-spinner-container">
        <SpinnerLegacy />
      </div>
    );
  }

  const showReserveBalanceSelfServeButton =
    !user.isOrgAxis &&
    user.isReserveBalanceSelfServeEnabled &&
    [rolesList.OWNER, rolesList.ADMIN].includes(user.role) &&
    !user.isCreditSelfServeDisabled;

  return (
    <Card>
      <CardHeader>
        <CardHeaderLeading
          title="Reserve Balance"
          subtitle="Add funds to your reserve balance to increase the negative balance limit."
        />
      </CardHeader>
      <CardBody>
        <Box
          display="flex"
          width="100%"
          flexDirection={{ base: 'column', l: 'row' }}
          justifyContent="space-between"
          gap="spacing.4"
        >
          {!resBalanceLoading ? (
            <Amount
              size="large"
              weight="semibold"
              type="heading"
              currency={user.merchant.currency}
              value={i18nifyConvertToMajorUnit(balance, user.merchant.currency)}
            />
          ) : (
            <Spinner marginY="auto" />
          )}
          {!user.isOrgAxis && !user.isReserveBalanceSelfServeEnabled && (
            <>
              {ticketGenerated || ticketStatusData.ticket_status === TICKET_STATUS.processing ? (
                <Button variant="secondary">Processing...</Button>
              ) : ticketStatusData.ticket_status === TICKET_STATUS.resolved ||
                ticketStatusData.ticket_status === TICKET_STATUS.closed ||
                balance > 0 ? null : (
                <Button variant="secondary" onClick={handleActivate}>
                  Activate
                </Button>
              )}
            </>
          )}

          {showReserveBalanceSelfServeButton ? (
            <Box display="flex" gap="spacing.3" alignItems="center">
              <WithdrawButton
                title="Reserve Balance"
                credits={balance}
                type="reserve_balance"
                submitHandler={fetchReserveBalanceAfterWithdrawal}
              />
              <div>
                <Button
                  variant="secondary"
                  isDisabled={mode !== 'live'}
                  onClick={() => handlAddFunds('reserve')}
                >
                  Add Funds
                </Button>
                {mode !== 'live' ? (
                  <Popover align="bottom" theme="dark">
                    <PopoverBody>
                      You cannot add funds in test mode. Switch to live mode to add funds.
                    </PopoverBody>
                  </Popover>
                ) : null}
              </div>
            </Box>
          ) : null}
        </Box>
        {ticketGenerated ||
        (ticketStatusData.ticket_status === 'Processing' &&
          !user.isReserveBalanceSelfServeEnabled) ? (
          <Text
            size="small"
            weight="medium"
            marginTop="spacing.2"
            color="interactive.text.positive.normal"
          >
            Your request is being processed. Please check your registered email for an update.
          </Text>
        ) : null}
      </CardBody>
    </Card>
  );
}
const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      fetchReserveBal: fetchReserveBalance,
    },
    dispatch,
  );
};

const mapStateToProps = (state) => {
  return {
    ...state.session,
    reserveBalance: state.profile.reserve_balance,
    ticketStatus: state.profile.ticket_status,
  };
};

export default connect(mapStateToProps, mapDispatchToProps)(ReserveBalance);
