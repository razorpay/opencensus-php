import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import AsyncButton from 'react-async-button';
import {
  updateDateRange,
  fetchSuccessRate,
  fetchMerchantErrors,
  setDefaultInterval,
  setDefaultLastUpdatedAt,
  setActiveTab,
  setCardTypeFilter,
} from 'merchant/reducers/successRate';
import {
  getBreakdownInterval,
  initialFilters,
  queryFilters,
  getMerchantErrorsPayload,
  validateDateRange,
} from 'merchant/views/Transactions/SuccessRate/helper';
import {
  PRESETS,
  DEFAULT_INTERVAL,
  INITIAL_SELECTED_CARD_TYPE,
} from 'merchant/views/Transactions/SuccessRate/constants';
import {
  clearFilterSuccessRate,
  filterSuccessRate,
  trackSuccessRateEvents,
} from 'merchant/views/Transactions/SuccessRate/trackEvents';
import { DateRangePreset } from './DateRangePreset';
import TabRefreshButton from './TabRefreshButton';

const SuccessRateFilter = (props) => {
  const {
    endDate,
    startDate,
    preset,
    fetchSuccessRate,
    fetchMerchantErrors,
    updateDateRange,
    setDefaultInterval,
    setDefaultLastUpdatedAt,
    activeTab,
    lastUpdatedAt,
    setActiveTab,
    setCardTypeFilter,
  } = props;

  const [dateRange, setDateRange] = useState({ startDate: '', endDate: '', preset: '' });
  const [errors, setErrors] = useState({});

  useEffect(() => {
    setDateRange({ startDate, endDate, preset });
  }, [startDate, endDate, preset]);

  useEffect(() => {
    const errors = validateDateRange(dateRange);
    setErrors(errors);
  }, [dateRange]);

  const onSearch = async (dateRangeParam = dateRange, errorsParam = errors) => {
    const isOverallTabActive = activeTab !== 'Overall';
    const { startDate, endDate } = dateRangeParam;

    if (Object.keys(errorsParam).length) return null;

    updateDateRange(dateRangeParam);
    setDefaultInterval(getBreakdownInterval(startDate, endDate));
    setDefaultLastUpdatedAt();

    if (activeTab === 'Card') setCardTypeFilter(INITIAL_SELECTED_CARD_TYPE);

    if (isOverallTabActive) {
      await fetchSuccessRate({
        payload: queryFilters(false, isOverallTabActive),
        refreshMetricTabs: isOverallTabActive,
        updateDropdownOptions: false,
      });
      return null;
    }

    const payload = queryFilters(isOverallTabActive);
    await fetchSuccessRate({ payload, updateDropdownOptions: isOverallTabActive });
    const errorsPaylod = getMerchantErrorsPayload(isOverallTabActive);
    fetchMerchantErrors(errorsPaylod);

    trackSuccessRateEvents(filterSuccessRate(payload));
    return null;
  };

  const onReset = async () => {
    const initialValue = initialFilters();
    const updateDropdownOptions = false;

    updateDateRange(initialValue);
    setActiveTab('Overall');
    setDefaultInterval(DEFAULT_INTERVAL);
    setDefaultLastUpdatedAt();

    const payload = queryFilters(updateDropdownOptions);
    await fetchSuccessRate({ payload, updateDropdownOptions });
    const errorsPaylod = getMerchantErrorsPayload(updateDropdownOptions);
    fetchMerchantErrors(errorsPaylod);

    trackSuccessRateEvents(clearFilterSuccessRate(payload));
  };

  const onPresetChange = ({ option, start, end }) => {
    const dateRange = {
      preset: option,
      startDate: start,
      endDate: end,
    };
    const errors = validateDateRange(dateRange);
    setDateRange(dateRange);
    setErrors(errors);
    if (option?.name !== 'custom') {
      onSearch(dateRange, errors);
    }
  };

  return (
    <div className="sr-filter">
      <div>
        <label>Date Range</label>

        <div className="datepicker-group">
          <DateRangePreset
            presets={PRESETS}
            dateRange={dateRange}
            setDateRange={setDateRange}
            setErrors={setErrors}
            onPresetChange={onPresetChange}
          />

          <div className="filter__actions">
            <AsyncButton
              className="btn btn-primary btn-sm"
              onClick={() => onSearch()}
              disabled={Object.keys(errors).length > 0}
              text="Search"
            />
            <AsyncButton className="btn btn-sm btn-text" onClick={onReset} text="Clear" />
          </div>
        </div>

        {errors.date && (
          <small class="error-text text-danger">
            <i className="i i-info-outline" />
            <i>{errors.date}</i>
          </small>
        )}
      </div>

      <TabRefreshButton
        timestamp={lastUpdatedAt}
        activeTab={activeTab}
        onRefresh={() => onSearch()}
      />
    </div>
  );
};

const mapStateToProps = ({ successRate }) => {
  const { filters = {}, tabs = {}, activeTab } = successRate;
  const { startDate, endDate, preset } = filters;

  return {
    startDate,
    endDate,
    preset,
    activeTab,
    lastUpdatedAt: tabs[activeTab]?.lastUpdatedAt,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      updateDateRange,
      fetchSuccessRate,
      fetchMerchantErrors,
      setDefaultInterval,
      setDefaultLastUpdatedAt,
      setActiveTab,
      setCardTypeFilter,
    },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(SuccessRateFilter);
