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
      if (!schedule.includes('default')) otherMethods.push(schedule.split(':')[1]);
    });
  }

  const toggleOtherMethods = () => {
    setShowOtherMethods(!showOtherMethods);
  };

  return (
    schedules[`${paymentType}:default`] && (
      <div className="payment-schedule-container">
        <div className="default-cycle schedule-row">
          <div className="section capitalize">{paymentType} Payments</div>
          <div className="section text-right">{schedules[`${paymentType}:default`]}</div>
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
                    >
                      <div className="section">{method.toUpperCase()}</div>
                      <div className="section text-right">
                        {schedules[`${paymentType}:${method}`]}
                      </div>
                    </div>
                  );
                })}
              </div>
            )}
          </div>
        )}
      </div>
    )
  );
};

export default PaymentSchedule;
