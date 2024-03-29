import { AlertOctagonIcon, Box, Button, Text } from '@razorpay/blade/components';
import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { useSplitzService } from 'common/splitz';
import {
  fetchMerchantErrors,
  fetchSuccessRate,
  resetSRDashboard,
  setActiveTab,
  setDefaultInterval,
  setDefaultLastUpdatedAt,
  setGroupTypeFilter,
  setMerchantIDSearch,
  updateDateRange,
} from 'merchant/reducers/successRate';
import {
  DEFAULT_GROUP_BY,
  DEFAULT_INTERVAL,
  PRESETS,
} from 'merchant/views/Transactions/v1/SuccessRate/constants';
import {
  getBreakdownInterval,
  getMerchantErrorsPayload,
  initialFilters,
  queryFilters,
  validateDateRange,
} from 'merchant/views/Transactions/v1/SuccessRate/helper';
import {
  clearFilterSuccessRate,
  filterSuccessRate,
  trackSuccessRateEvents,
} from 'merchant/views/Transactions/v1/SuccessRate/trackEvents';

import { DateRangePreset } from './DateRangePreset';
import SearchMerchant from './SearchMerchant';
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
    tab,
    setActiveTab,
    setGroupTypeFilter,
    isSrAdminEnabled,
    setMerchantIDSearch,
    isLoading,
  } = props;
  const splitz = useSplitzService();
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

    if (Object.keys(errorsParam).length) return;

    updateDateRange(dateRangeParam);
    setDefaultInterval(getBreakdownInterval(startDate, endDate));
    setDefaultLastUpdatedAt();

    if (activeTab === 'Card') {
      setGroupTypeFilter(DEFAULT_GROUP_BY[activeTab]);
    }

    if (isOverallTabActive) {
      await fetchSuccessRate({
        payload: queryFilters(false, isOverallTabActive),
        refreshMetricTabs: isOverallTabActive,
        updateDropdownOptions: false,
      });
    }

    const payload = queryFilters(isOverallTabActive);
    await fetchSuccessRate({ payload, updateDropdownOptions: isOverallTabActive });
    const errorsPaylod = getMerchantErrorsPayload(isOverallTabActive);
    fetchMerchantErrors(errorsPaylod);
    trackSuccessRateEvents(filterSuccessRate(payload), splitz);
  };

  const resetToInitialState = () => {
    const initialValue = initialFilters();
    updateDateRange(initialValue);
    setActiveTab('Overall');
    setDefaultInterval(DEFAULT_INTERVAL);
    setDefaultLastUpdatedAt();
  };

  const onReset = async () => {
    resetToInitialState();
    const updateDropdownOptions = false;

    const payload = queryFilters(updateDropdownOptions);
    await fetchSuccessRate({ payload, updateDropdownOptions });
    const errorsPaylod = getMerchantErrorsPayload(updateDropdownOptions);
    fetchMerchantErrors(errorsPaylod);
    trackSuccessRateEvents(clearFilterSuccessRate(payload), splitz);
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

  const handleSearch = () => onSearch(dateRange, errors);

  const handleSearchByMerchantId = (merchantId) => {
    resetToInitialState();
    setMerchantIDSearch(merchantId);
    const payload = queryFilters();
    const errorsPayload = getMerchantErrorsPayload();
    fetchSuccessRate({ payload });
    fetchMerchantErrors(errorsPayload);
  };

  const onResetMerchantSearch = () => {
    resetToInitialState();
    setMerchantIDSearch('');
    const payload = queryFilters();
    const errorsPayload = getMerchantErrorsPayload();
    fetchSuccessRate({ payload });
    fetchMerchantErrors(errorsPayload);
  };

  return (
    <div className="sr-filter" data-testid="success-rate-filter">
      <Box>
        <Box>
          <Text marginBottom="spacing.2" size="large">
            Date Range
          </Text>
          <div className="datepicker-group">
            <DateRangePreset
              presets={PRESETS}
              dateRange={dateRange}
              setDateRange={setDateRange}
              setErrors={setErrors}
              onPresetChange={onPresetChange}
            />

            <div className="filter__actions">
              <Button
                variant="primary"
                onClick={handleSearch}
                isDisabled={Object.keys(errors).length > 0}
                testID="sr-filter-apply-btn"
              >
                Apply
              </Button>
              <Button
                variant="tertiary"
                onClick={onReset}
                marginLeft="spacing.3"
                testID="sr-filter-clear-btn"
              >
                Clear
              </Button>
            </div>
          </div>
        </Box>

        {errors.date ? (
          <Box display="flex" alignItems="center" testID="sr-filter-error">
            <AlertOctagonIcon size="medium" color="interactive.icon.negative.subtle" />
            <Text color="interactive.text.negative.subtle" marginLeft="spacing.2">
              {errors.date}
            </Text>
          </Box>
        ) : null}
      </Box>
      <div className="sr-filter-extras">
        {isSrAdminEnabled ? (
          <SearchMerchant
            onSearch={handleSearchByMerchantId}
            onReset={onResetMerchantSearch}
            isLoading={isLoading}
          />
        ) : null}
        <TabRefreshButton
          timestamp={tab?.lastUpdatedAt}
          activeTab={activeTab}
          onRefresh={handleSearch}
        />
      </div>
    </div>
  );
};

const mapStateToProps = ({ session, successRate }) => {
  const { user } = session;
  const { filters, tabs, activeTab, searchedMerchantId, isLoading } = successRate;
  const { startDate, endDate, preset } = filters;

  return {
    startDate,
    endDate,
    preset,
    activeTab,
    tab: tabs[activeTab],
    searchedMerchantId,
    isLoading,
    isSrAdminEnabled: user.isSrAdminEnabled,
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
      setGroupTypeFilter,
      setMerchantIDSearch,
      resetSRDashboard,
    },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(SuccessRateFilter);
