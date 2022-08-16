import React from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import DateRangePicker from 'common/ui/DateRangePicker';
import AsyncButton from 'react-async-button';
import {
  updateDateRange,
  fetchSuccessRate,
  fetchMerchantErrors,
} from 'merchant/reducers/successRate';
import { getInterval, initialFilters, queryFilters, getMerchantErrorsPayload } from '../helper';
import { DATE_RANGE_PRESETS, DEFAULT_PRESET } from '../constants';

const SucessRateFilter = (props) => {
  const { endDate, fetchSuccessRate, fetchMerchantErrors, updateDateRange } = props;

  const onDatesChange = (startDate, endDate, preset) => {
    const interval = getInterval(startDate, endDate);
    updateDateRange({
      startDate,
      endDate,
      interval,
      preset,
    });
  };

  const onSearch = async () => {
    const payload = queryFilters();
    await fetchSuccessRate(payload);
    const errorsPaylod = getMerchantErrorsPayload();
    await fetchMerchantErrors(errorsPaylod);
  };

  const onReset = async () => {
    const payload = initialFilters();
    await fetchSuccessRate(payload);
  };

  return (
    <div className="filter">
      <div className="datepicker-group">
        <label>Duration</label>
        {/* For m-web we want to show only one month to support mweb view */}
        <DateRangePicker
          defaultPreset={DEFAULT_PRESET}
          presets={DATE_RANGE_PRESETS}
          endDate={endDate}
          onDatesChange={onDatesChange}
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
