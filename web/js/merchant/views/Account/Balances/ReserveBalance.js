import React from 'react';
import { connect } from 'react-redux';
import Spinner from 'common/ui/Spinner';
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
} from '@razorpay/blade/components';
import { Flex } from '../Credits/components/style';
import { TICKET_STATUS } from '../constants';
import { i18nifyConvertToMajorUnit } from 'merchant/views/Transactions/v2/common/utils';

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
  mode,
}) {
  const items = reserveBalance.data?.items;
  const balance = getReserveBalanceAmount(items, reserveBalance.error);
  const { data: ticketStatusData, loading: ticketStatusLoading } = ticketStatus;

  if (ticketStatusLoading) {
    return (
      <div class="page-spinner-container">
        <Spinner />
      </div>
    );
  }

  return (
    <Card>
      <CardHeader>
        <CardHeaderLeading
          title="Reserve Balance"
          subtitle="Add funds to your reserve balance to increase the negative balance limit."
        />
      </CardHeader>
      <CardBody>
        <Flex isResponsive spacing={8} justifyBetween direction="row">
          <Amount
            size="large"
            weight="semibold"
            type="heading"
            currency={user.merchant.currency}
            value={i18nifyConvertToMajorUnit(balance, user.merchant.currency)}
          />
          {!user.isOrgAxis && !user.isReserveBalanceSelfServeEnabled && (
            <>
              {ticketGenerated || ticketStatusData.ticket_status === TICKET_STATUS.processing ? (
                <button class="btn btn-primary">Processing...</button>
              ) : ticketStatusData.ticket_status === TICKET_STATUS.resolved ||
                ticketStatusData.ticket_status === TICKET_STATUS.closed ||
                balance > 0 ? null : (
                <Button variant="secondary" onClick={handleActivate}>
                  Activate
                </Button>
              )}
            </>
          )}

          {!user.isOrgAxis &&
            user.isReserveBalanceSelfServeEnabled &&
            [rolesList.OWNER, rolesList.ADMIN].includes(user.role) && (
              <Box display="flex" gap="spacing.3" alignItems="center">
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
            )}
        </Flex>
      </CardBody>
    </Card>
  );
}

const mapStateToProps = (state) => {
  return {
    ...state.session,
    reserveBalance: state.profile.reserve_balance,
    ticketStatus: state.profile.ticket_status,
  };
};

export default connect(mapStateToProps, null)(ReserveBalance);
