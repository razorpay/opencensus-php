import React, { useState } from 'react';

const PaymentSchedule = (props) => {
  const { paymentType, schedules } = props;
  const [showOtherMethods, setShowOtherMethods] = useState(false);
  const otherMethods = [];
  const allSchedules = Object.keys(schedules);
  const filteredSchedules = allSchedules.filter((schedule) => {
    return schedule.includes(paymentType);
  });

  if (filteredSchedules.length > 1) {
    filteredSchedules.forEach((schedule) => {
      if (!schedule.includes('in_person') && !schedule.includes('default')) {
        otherMethods.push(schedule.split(':')[1]);
      }
    });
  }

  const toggleOtherMethods = () => {
    setShowOtherMethods(!showOtherMethods);
  };

  return schedules[`${paymentType}:default`] ? (
    <div className="payment-schedule-container">
      <div className="default-cycle schedule-row">
        <div className="section capitalize">{paymentType} Payments</div>
        <div className="section capitalize text-right">
          <strong>{schedules[`${paymentType}:default`]}</strong>
        </div>
      </div>
      {otherMethods?.length > 0 && (
        <div className="other-methods-container">
          <span className="pr-5">
            <strong>Note:</strong> Some {paymentType} payment methods have a different schedule.
          </span>
          <span className="btn-link" onClick={toggleOtherMethods}>
            <span className="text-no-wrap">{showOtherMethods ? 'Hide' : 'View'} schedules</span>
            <i className={`i i-arrow-${showOtherMethods ? 'up' : 'down'}`} />
          </span>
          {showOtherMethods && (
            <div className="mt-12">
              <div className="schedule-row other-methods-heading mb-6">
                <div className="section">Payment method</div>
                <div className="section text-right">Settlement schedule</div>
              </div>
              {otherMethods.map((method, index) => {
                return (
                  <div
                    className={`${
                      index !== otherMethods.length - 1 ? 'mb-6 ' : ''
                    }schedule-row other-methods-row`}
                    key={`${paymentType}:${method}`}
                    aria-label="methods"
                  >
                    <div className="section capitalize">{method}</div>
                    <div className="section capitalize text-right">
                      <strong>{schedules[`${paymentType}:${method}`]}</strong>
                    </div>
                  </div>
                );
              })}
            </div>
          )}
        </div>
      )}
    </div>
  ) : null;
};

export default PaymentSchedule;
