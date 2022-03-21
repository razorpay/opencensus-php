import React, { useState } from 'react';
import PropTypes from 'prop-types';
import messageFactory from 'merchant/views/Settlements/InstantSettlements/InstantSettlements/SettlementMessage/messageFactory';
import Message from 'merchant/views/Settlements/InstantSettlements/InstantSettlements/SettlementMessage/Message';
import {
  checkDateIsWeekend,
  checkDateIsUpcomingBankHoliday,
} from 'merchant/views/Settlements/InstantSettlements/utils/check-date';
import {
  setEsBannerSeen,
  getEsBannerSeen,
  getNoOfDaysAfterEsPartialEnable,
} from 'merchant/views/Settlements/Settlements/components/Modals/ScheduledModal/utils';

import { NEW_BANNERS } from './banners/constants';
import EnableAutomatic from './banners/EnabledAutomatic';
import FullShiftSuccess from './banners/FullShiftSuccess';
import FullShiftFailure from './banners/FullShiftFailure';

const SettlementMessageContainer = ({ user, holidayList, openModal, balance }) => {
  // eslint-disable-next-line no-unused-vars
  const [dismissed, setDismissed] = useState(false);

  const date = new Date();
  const isWeekend = checkDateIsWeekend(date);
  const upcomingBankHoliday = checkDateIsUpcomingBankHoliday(date, holidayList);
  const isBankingDay = !isWeekend && !upcomingBankHoliday;

  const diff = getNoOfDaysAfterEsPartialEnable();
  const isOndemandSettlementsRestricted = user.isOndemandSettlementsRestricted;
  const isOndemandSettlementEnabled = user.isOndemandSettlementEnabled;
  const isFullOndemandSettlementEnabled =
    isOndemandSettlementEnabled && !isOndemandSettlementsRestricted;
  const isPartialOndemandSettlementEnabled =
    isOndemandSettlementEnabled && isOndemandSettlementsRestricted;
  const { isAutomaticSettlementEnabled, isAutomaticSettlementRestricted, isOrgRZP } = user;

  function getPayoutMessageData() {
    if (!isAutomaticSettlementEnabled && !isAutomaticSettlementRestricted && isOrgRZP) {
      return messageFactory.getEnableScheduledSettlements();
    } else if (isBankingDay && balance < 100) {
      return messageFactory.getSettlementWillBeSkipped();
    } else if (upcomingBankHoliday && upcomingBankHoliday.dayDifference === 0) {
      return messageFactory.getBankHolidayToday(upcomingBankHoliday);
    } else if (upcomingBankHoliday) {
      return messageFactory.getBankHolidayUpcoming(upcomingBankHoliday);
    } else if (diff) {
      if (
        isFullOndemandSettlementEnabled &&
        isAutomaticSettlementEnabled &&
        !getEsBannerSeen(NEW_BANNERS.FULL_SHIFT_SUCCESS)
      ) {
        return messageFactory.getFullShiftSuccess();
      } else if (
        diff > 30 &&
        isPartialOndemandSettlementEnabled &&
        isAutomaticSettlementRestricted &&
        !getEsBannerSeen(NEW_BANNERS.FULL_SHIFT_FAILURE)
      ) {
        return messageFactory.getFullShiftFailure();
      }
    }
    return messageFactory.getSettleNonBankingHoursNudge();
  }

  const message = getPayoutMessageData();

  const onDismiss = (bannerType) => {
    setEsBannerSeen(bannerType);
    setDismissed(true);
  };

  const getNewBanner = () => {
    switch (message) {
      case NEW_BANNERS.ENABLE_ES_AUTOMATIC:
        return <EnableAutomatic openModal={openModal} />;
      case NEW_BANNERS.FULL_SHIFT_SUCCESS:
        return <FullShiftSuccess openModal={openModal} onDismiss={onDismiss} />;
      case NEW_BANNERS.FULL_SHIFT_FAILURE:
        return <FullShiftFailure openModal={openModal} onDismiss={onDismiss} />;
      default:
        return null;
    }
  };

  if (Object.values(NEW_BANNERS).includes(message)) {
    return getNewBanner();
  }

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
