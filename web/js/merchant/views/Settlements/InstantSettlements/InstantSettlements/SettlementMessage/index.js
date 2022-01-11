import React from 'react';
import PropTypes from 'prop-types';
import messageFactory from 'merchant/views/Settlements/InstantSettlements/InstantSettlements/SettlementMessage/messageFactory';
import Message from 'merchant/views/Settlements/InstantSettlements/InstantSettlements/SettlementMessage/Message';
import {
  checkDateIsWeekend,
  checkDateIsUpcomingBankHoliday,
} from 'merchant/views/Settlements/InstantSettlements/utils/check-date';

const SettlementMessageContainer = ({ user, holidayList, openModal, balance }) => {
  const date = new Date();
  const isWeekend = checkDateIsWeekend(date);
  const upcomingBankHoliday = checkDateIsUpcomingBankHoliday(date, holidayList);
  const isBankingDay = !isWeekend && !upcomingBankHoliday;

  function getPayoutMessageData() {
    if (!user.isAutomaticSettlementEnabled && !user.isOndemandSettlementsRestricted) {
      return messageFactory.getEnableScheduledSettlements();
    } else if (isBankingDay && balance < 100) {
      return messageFactory.getSettlementWillBeSkipped();
    } else if (upcomingBankHoliday && upcomingBankHoliday.dayDifference === 0) {
      return messageFactory.getBankHolidayToday(upcomingBankHoliday);
    } else if (upcomingBankHoliday) {
      return messageFactory.getBankHolidayUpcoming(upcomingBankHoliday);
    }
    return messageFactory.getSettleNonBankingHoursNudge();
  }

  const message = getPayoutMessageData();

  return (
    <div className="payout-message-date">
      <Message {...message} openModal={openModal} />
      {!message.hideRightImages && (
        <>
          <div className="payout-message-date--svg-up">
            <img src="/dist/css/assets/capital/payout-date-message-up.svg" />
          </div>
          <div className="payout-message-date--svg-down">
            <img src="/dist/css/assets/capital/payout-date-message-down.svg" />
          </div>
        </>
      )}
    </div>
  );
};

SettlementMessageContainer.propTypes = {
  user: PropTypes.object,
  holidayList: PropTypes.object,
  openModal: PropTypes.func,
  balance: PropTypes.number,
};

export default SettlementMessageContainer;
