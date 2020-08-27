import React from 'react';
import ModalHeader from 'common/ui/ModalHeader';
import Button from 'common/new-ui/Button';
import { getNextWithdrawableDate } from '../utils/ApolloFinvestValidations';

function WorkingHoursPrompt({ closeModal, trackGA }) {
  const nextWithdrawableDate = getNextWithdrawableDate(moment());
  return (
    <div class="lender-unserviceable-modal withdrawals">
      <ModalHeader
        class="header"
        title={
          <div class="flex">
            <img src={`/dist/css/assets/capital/question_circle.svg`} alt="Loading icon" /> &nbsp;
            <p>Why Can’t I withdraw?</p>
          </div>
        }
        onCloseClick={() => {
          trackGA({
            eventAction: 'Apollo Non-Working Hours | Close Icon',
          });
          closeModal();
        }}
      />
      <small class="text-fade description">Check the reason for not able to withdraw money.</small>
      <div className="overflow-box">
        <div class="service-hours-message">
          <p class="no-margin">
            <strong>Sorry</strong>, Withdrawal is not available on Saturday & Sunday (weekends),
            hence please try to withdraw money on working day from {nextWithdrawableDate}
          </p>
        </div>
        <img
          src={`/dist/css/assets/capital/apollo_finvest_service_days.svg`}
          class="full-width no-margin"
          alt="Loading icon"
        />
        <Button.Primary
          className="full-width m-t"
          onClick={() => {
            trackGA({
              eventAction: "Apollo Non-Working Hours | I'll do later",
            });

            closeModal();
          }}
        >
          <strong>Ok, I'll do later</strong>
        </Button.Primary>
      </div>
      <div class="next-working-day-message">
        <div className="summary">Next Withdrawable Date</div>
        <strong>{nextWithdrawableDate}</strong>
      </div>
    </div>
  );
}

export default WorkingHoursPrompt;
