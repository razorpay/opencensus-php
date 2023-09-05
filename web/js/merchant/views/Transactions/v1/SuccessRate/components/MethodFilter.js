import React from 'react';
import styled from 'styled-components';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import {
  fetchSuccessRate,
  fetchMerchantErrors,
  setFailureReasonType,
  setMethodType,
  setRecurringType,
} from 'merchant/reducers/successRate';
import {
  METHOD_TYPES_MAP,
  CARD_RECURRING,
} from 'merchant/views/Transactions/v1/SuccessRate/constants';
import GroupingDropdown from 'merchant/containers/Home/GroupingDropdown';
import {
  checkIfFilterValid,
  queryFilters,
  getMerchantErrorsPayload,
} from 'merchant/views/Transactions/v1/SuccessRate/helper';
import MethodType from './MethodType';
import RecurringType from './RecurringType';

const MethodFilter = (props) => {
  const {
    tab,
    filtersList,
    handleGroupingChange,
    disabled,
    selectedGrouping,
    isInternationalEnabled,
    activeTab,
    fetchSuccessRate,
    fetchMerchantErrors,
    setFailureReasonType,
    user,
    setMethodType,
    setRecurringType,
  } = props;

  const { selectedMethodType, selectedRecurringType } = tab;

  const renderGroupingDropdown = (groupingData = [], index) => {
    const filteredGroupingData = groupingData.filter(({ value }) =>
      checkIfFilterValid({ activeTab, filter: value, flags: { isInternationalEnabled } }),
    );

    if (filteredGroupingData.length > 0) {
      return (
        <GroupingDropdown
          key={index}
          className={`sr-tab__filter ${disabled ? ' PowerSelect--disabled' : ''}`}
          onGroupChange={handleGroupingChange}
          grouping={filteredGroupingData}
          selectedGrouping={selectedGrouping?.[index] || filteredGroupingData?.[0]}
        />
      );
    }
    return null;
  };

  const DropdownWrapper = styled.div`
    position: relative;
    margin-right: auto;
  `;

  const FilterWrapper = styled.div`
    position: relative;
    margin-right: auto;
  `;

  const handleMethodTypeChange = (selectedValue) => {
    if (selectedMethodType === selectedValue) return;

    setMethodType(selectedValue);
    setFailureReasonType('default');
    const updateDropdownOptions = activeTab !== 'Overall';
    const payload = queryFilters(updateDropdownOptions);
    fetchSuccessRate({ payload, resetSelectedInterval: false });
    const errorsPaylod = getMerchantErrorsPayload();
    fetchMerchantErrors(errorsPaylod);
  };

  const currentMethodType = METHOD_TYPES_MAP[activeTab];
  const isRenderMethodTypes = currentMethodType?.shouldRender?.({
    user,
  });

  const handleRecurringTypeChange = (selectedValue) => {
    if (selectedRecurringType === selectedValue) return;

    setRecurringType(selectedValue);
    setFailureReasonType('default');
    const updateDropdownOptions = activeTab !== 'Overall';
    const payload = queryFilters(updateDropdownOptions);
    fetchSuccessRate({ payload, resetSelectedInterval: false });
    const errorsPaylod = getMerchantErrorsPayload();
    fetchMerchantErrors(errorsPaylod);
  };

  const isCardRecurring = activeTab === CARD_RECURRING;

  return (
    <div className="sr-filter sr-method-filters flex">
      {isRenderMethodTypes && currentMethodType?.types?.length ? (
        <MethodType
          currentMethodType={currentMethodType}
          selectedMethodType={selectedMethodType}
          handleMethodTypeChange={handleMethodTypeChange}
        />
      ) : null}
      {currentMethodType?.recurringTypes?.length ? (
        isCardRecurring ? (
          <DropdownWrapper>
            <div>
              <RecurringType
                currentMethodType={currentMethodType}
                selectedRecurringType={selectedRecurringType}
                handleRecurringTypeChange={handleRecurringTypeChange}
              />
            </div>
          </DropdownWrapper>
        ) : (
          <div>
            <RecurringType
              currentMethodType={currentMethodType}
              selectedRecurringType={selectedRecurringType}
              handleRecurringTypeChange={handleRecurringTypeChange}
            />
          </div>
        )
      ) : null}
      {filtersList.length ? (
        <FilterWrapper>
          <label>Filter:</label>
          <div data-testid="group-filters-dropdown" className="flex">
            {filtersList?.map(renderGroupingDropdown)}
          </div>
        </FilterWrapper>
      ) : null}
    </div>
  );
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      fetchSuccessRate,
      fetchMerchantErrors,
      setFailureReasonType,
      setMethodType,
      setRecurringType,
    },
    dispatch,
  );
};

export default connect(null, mapDispatchToProps)(MethodFilter);
