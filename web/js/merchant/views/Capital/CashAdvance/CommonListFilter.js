import React, { useRef, useState } from 'react';
import { Field } from 'redux-form';

import {
  STATUS_FILTER_OPTIONS,
  CASH_ADVANCE_SECTIONS,
  DEFAULT_PERIOD_OPTIONS,
  REPAYMENT_FILTER_STATUS_OPTIONS,
} from './constants';
import ListFilter from 'merchant/components/ListFilter';
import SelectPeriod from 'merchant_common/containers/ReportsAsync/GenerateReportPanel/SelectPeriod';

export default ({
  showBatchIdFilter,
  view,
  repayments,
  showPeriodSelect = false,
  onSubmit,
  maxCountLimit = 25,
  ...props
}) => {
  const [dateRangeError, setDateRangeError] = useState('');
  const isRepaymentView = view === CASH_ADVANCE_SECTIONS.REPAYMENTS;
  const STATUS_OPTIONS = isRepaymentView ? REPAYMENT_FILTER_STATUS_OPTIONS : STATUS_FILTER_OPTIONS;

  let dateRef = useRef(null);

  function onDateRangeChanges(startAt, endAt) {
    const difference = endAt.diff(startAt, 'days');
    let error = '';

    if (difference < 0) {
      error = "Start at date can't exceed end at date";
    }

    setDateRangeError(error);
  }

  function onSubmitWrapper(data) {
    if (dateRef?.getDateRange) {
      const [from, to] = dateRef.getDateRange() || [];
      onSubmit({ ...data, from, to });
    } else onSubmit(data);
  }

  return (
    <ListFilter {...props} onSubmit={onSubmitWrapper}>
      <div className="form-group list-filter-item">
        <label>{`${isRepaymentView ? 'Repayment' : 'Withdrawal'}`} ID</label>
        <Field name="reference_id" component="input" className="form-control input-sm" />
      </div>
      <div className="form-group list-filter-item">
        <label>Status</label>
        <Field name="status" component="select" className="form-control input-sm">
          <option value="">All</option>
          {Object.entries(STATUS_OPTIONS).map(([value, label], idx) => (
            <option value={value} key={idx}>
              {label}
            </option>
          ))}
        </Field>
      </div>

      {showPeriodSelect && (
        <div className="form-group list-filter-item" id="select-period">
          <SelectPeriod
            avlblPeriodOptions={DEFAULT_PERIOD_OPTIONS}
            ref={(ref) => (dateRef = ref)}
            isFormDisabled={false}
            dateRangeError={dateRangeError}
            onDateRangeChanges={onDateRangeChanges}
            defaultPeriod="all"
          />
        </div>
      )}

      <div className={`form-group list-filter-item count ${showPeriodSelect ? 'ml-0' : ''}`}>
        <label>Count</label>
        <Field
          name="count"
          component="input"
          min={10}
          max={maxCountLimit}
          type="number"
          className="form-control input-sm"
        />
      </div>
    </ListFilter>
  );
};
