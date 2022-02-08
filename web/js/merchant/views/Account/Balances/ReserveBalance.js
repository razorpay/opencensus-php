import React from 'react';
import { connect } from 'react-redux';
import Amount from 'common/ui/Amount';
import Spinner from 'common/ui/Spinner';
import Popover, { PopoverBody } from 'common/ui/Popover';
import rolesList from 'merchant/helpers/permissions/roles-list';

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
    <>
      <div class="balances-container">
        <div class="bal-cont-header">
          <div class="balances-lhs-container">
            <div class="balance-type-container">
              <p>Reserve Balance</p>
            </div>
            <div class="balance-amount-container">
              <Amount value={Math.abs(balance)} currency="INR" />
            </div>
          </div>
          {!user.isOrgAxis && !user.isReserveBalanceSelfServeEnabled && (
            <div class="balances-add-funds">
              {ticketGenerated || ticketStatusData.ticket_status === 'Processing' ? (
                <button class="btn btn-primary">Processing...</button>
              ) : ticketStatusData.ticket_status === 'Resolved' ||
                ticketStatusData.ticket_status === 'Closed' ||
                balance > 0 ? null : (
                <button class="btn btn-outline" onClick={handleActivate}>
                  Activate
                </button>
              )}
            </div>
          )}
          {!user.isOrgAxis &&
            user.isReserveBalanceSelfServeEnabled &&
            [rolesList.OWNER, rolesList.ADMIN].includes(user.role) && (
              <div class="balances-add-funds">
                <div>
                  <button
                    class="btn btn-outline"
                    disabled={mode !== 'live'}
                    onClick={() => handlAddFunds('reserve')}
                  >
                    Add Funds
                  </button>
                  {mode !== 'live' ? (
                    <Popover align="bottom" theme="dark">
                      <PopoverBody>
                        You cannot add funds in test mode. Switch to live mode to add funds.
                      </PopoverBody>
                    </Popover>
                  ) : null}
                </div>
              </div>
            )}
        </div>
        <div class="bal-cont-footer">
          <p>
            Add funds to your reserve balance to increase the negative balance limit. Thinking of
            withdrawing your reserve balance? <a onClick={handleContactUs}>Contact Us</a>
          </p>
        </div>
      </div>

      {ticketGenerated ||
      (ticketStatusData.ticket_status === 'Processing' &&
        !user.isReserveBalanceSelfServeEnabled) ? (
        <div class="processing-note">
          <p>Your request is being processed. Please check your registered email for an update.</p>
        </div>
      ) : null}
    </>
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
