import React, { useMemo } from 'react';
import GistInfoCard from 'common/ui/GistInfoCard';

const getDisplayText = (type) => {
  return {
    number_of_total_payments: {
      title: 'Total Attempts',
      helpText: 'Number of times customer clicked on pay now button',
    },
    number_of_successful_payments: {
      title: 'Successful Payments',
      helpText: 'Number of times customer successfull completed the payments',
    },
    customer_dropp_off: {
      title: 'Customer Drop-offs',
      helpText: 'Failures due to customer cancellations, incorrect CVV, insufficient funds etc',
    },
    bank_failure: {
      title: 'Bank Failures',
      helpText: 'Failures due to issues at customer’s bank, UPI app, wallets etc',
    },
    business_failure: {
      title: 'Business Failures',
      helpText: 'Failues due to non-activation of payment methods, international payments etc',
    },
    other_failure: {
      title: type === 'variant1' ? 'Other Failures' : 'Razorpay Failures',
      helpText: 'Failures due to fraud detection, internal razorpay issues etc',
    },
  };
};

export default ({ data, user }) => {
  // Method to get the mismatch btwn total <=> fail + Success
  const mismatchCount = useMemo(() => {
    const totalData = data?.summary?.number_of_total_payments;
    const totalSuccess = data?.summary?.number_of_successful_payments;
    let totalFailed = 0;
    Object.keys(data?.failure_details || {}).forEach(
      (item) => (totalFailed += data?.failure_details[item]),
    );
    return totalData - (totalSuccess + totalFailed) || null;
  }, [data]);

  // Method to check if the min threshold of no of payment is reached in order to show the FA
  const isValidShow = useMemo(() => {
    return data?.summary?.number_of_total_payments >= parseInt(user.showFAPaymantCount, 10);
  }, [data, user]);

  const DISPLAY_TEXT = getDisplayText(user.faTextVariant);
  return data && isValidShow ? (
    <>
      <div className={`failure-analysis-container${!mismatchCount ? ' add-gap' : ''}`}>
        <div className="content">
          {Object.keys(data)?.map((item) => (
            <div key={item} className={item === 'summary' ? 'overall-data' : 'each-failure-data'}>
              {item &&
                Object.keys(data[item])?.map((eachItem) => (
                  <GistInfoCard
                    key={eachItem}
                    data={{
                      title: DISPLAY_TEXT[eachItem]?.title,
                      value: data[item][eachItem],
                      helpText: DISPLAY_TEXT[eachItem]?.helpText,
                      experiment: user.faTextVariant,
                    }}
                    tooltipAlign={
                      item === 'failure_details' && eachItem === 'other_failure' ? 'right' : 'left'
                    }
                  />
                ))}
              {item === 'summary' && <div className="data-devider" />}
            </div>
          ))}
        </div>
      </div>
      {mismatchCount && (
        <div className="failure-analysis-mismatch-desc">
          <i className="fa fa-exclamation-circle text-fade" /> {mismatchCount} payments are in
          created state and are yet to be processed. They are counted in total attempts but not in
          successful or failed payments.
        </div>
      )}
    </>
  ) : null;
};
