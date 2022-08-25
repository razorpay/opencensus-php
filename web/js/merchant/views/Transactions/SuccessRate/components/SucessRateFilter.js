import React, { useCallback } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import moment from 'moment';
import AsyncButton from 'react-async-button';
import DateRangePicker from 'common/ui/DateRangePicker';
import {
  updateDateRange,
  fetchSuccessRate,
  fetchMerchantErrors,
} from 'merchant/reducers/successRate';
import { getInterval, initialFilters, queryFilters, getMerchantErrorsPayload } from '../helper';
import { DATE_RANGE_PRESETS, DEFAULT_PRESET } from '../constants';

const SucessRateFilter = (props) => {
  const { endDate, fetchSuccessRate, fetchMerchantErrors, updateDateRange } = props;

  const isOutsideRange = useCallback((day) => {
    const currentDay = moment();
    const pastDay = currentDay.clone().subtract(90, 'days');
    return day.isAfter(currentDay) || day.isBefore(pastDay);
  }, []);

  const onDatesChange = (from, to, preset) => {
    const start_date = from.clone().startOf('hour');
    const end_date = to.clone().endOf('hour');
    const interval = getInterval(start_date, end_date);
    updateDateRange({
      startDate: start_date,
      endDate: end_date,
      interval,
      preset,
    });
  };

  const onSearch = () => {
    const payload = queryFilters();
    fetchSuccessRate(payload);
    const errorsPaylod = getMerchantErrorsPayload();
    fetchMerchantErrors(errorsPaylod);
  };

  const onReset = async () => {
    const initialValue = initialFilters();
    updateDateRange(initialValue);
    const payload = queryFilters();
    await fetchSuccessRate(payload);
    const errorsPaylod = getMerchantErrorsPayload();
    await fetchMerchantErrors(errorsPaylod);
  };

  return (
    <div className="filter">
      <div className="datepicker-group">
        <label>Date Range</label>
        {/* For m-web we want to show only one month to support mweb view */}
        <DateRangePicker
          defaultPreset={DEFAULT_PRESET}
          presets={DATE_RANGE_PRESETS}
          endDate={endDate}
          onDatesChange={onDatesChange}
          isOutsideRange={isOutsideRange}
          // onSelectPreset={trackPresetChange} TODO: Setup google analytics
        />
      </div>
      <div className="filter__actions">
        <AsyncButton className="btn btn-primary btn-sm" onClick={onSearch} text="Search" />
        <AsyncButton className="btn btn-sm btn-text" onClick={onReset} text="Clear" />
      </div>
    </div>
  );
};

const mapStateToProps = ({ successRate }) => ({
  endDate: successRate?.filters?.endDate,
});

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators({ updateDateRange, fetchSuccessRate, fetchMerchantErrors }, dispatch);
};

export default connect(mapStateToProps, mapDispatchToProps)(SucessRateFilter);
