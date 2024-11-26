import React, { useMemo, useRef, useState } from 'react';
import {
  Box,
  Button,
  FilterIcon,
  SearchIcon,
  Tag,
  TextInput,
  Tooltip,
  TooltipInteractiveWrapper,
} from '@razorpay/blade/components';
import moment from 'moment';
import isEmpty from 'lodash/isEmpty';
import lazy from '@dashboard/shared-utils/routes/LazyLoader';

import { Option } from '@dashboard/shared-ui/components/Dropdown/types';
import {
  disputeDurationSectionName,
  searchByOptionsMap,
  statusSectionName,
} from 'apps/self-serve/src/App/Transactions/v2/Disputes/constants';
import {
  DisputeListFilterProps,
  SearchArgs,
  SelectedFilterType,
} from 'apps/self-serve/src/App/Transactions/v2/Disputes/types';
import {
  getDefaultFilterSelectedOptions,
  getDefaultValuesAndOptions,
  getOptions,
  getTags,
  getValidFilterKeys,
} from 'apps/self-serve/src/App/Transactions/v2/Disputes/utils';
import {
  CUSTOM,
  DESKTOP_CALENDAR_NUMBER_OF_MONTHS,
  LAST_7_DAYS,
  MOBILE_CALENDAR_NUMBER_OF_MONTHS,
  mobileBreakoints,
  TransactionsPagesMap,
} from 'apps/self-serve/src/App/Transactions/v2/common/constants';
import {
  StyledDateRangePicker,
  StyledListFilter,
  StyledSearchByFilter,
  StyledSubListFilter,
} from 'apps/self-serve/src/App/Transactions/v2/common/styled';
import {
  trackDurationFilter,
  trackSearchButton,
  track,
  trackStatusFilter,
} from 'apps/self-serve/src/App/Transactions/v2/common/tracking';
import { Duration, DurationOption } from 'apps/self-serve/src/App/Transactions/v2/common/types';
import {
  endOfDay,
  getFromTime,
  getValue,
} from 'apps/self-serve/src/App/Transactions/v2/common/utils';
import { SuspenseWithLoader } from '@dashboard/shared-ui/components';
import { useMobile } from '@dashboard/shared-ui/hooks';
import DisputeFilter from 'apps/self-serve/src/App/Transactions/v2/Disputes/components/DisputeFilter';
import { withRouter } from 'shell/deprecated/withRouter';
import Dropdown from '@dashboard/shared-ui/components/Dropdown';

const DateRangePicker = lazy(
  () => import(/* webpackChunkName: 'DateRangePicker' */ 'common/ui/Forms/DateRangePickerField'),
);

const DisputeListFilter = ({
  onSubmit,
  loading,
  location: { pathname },
}: DisputeListFilterProps): JSX.Element => {
  const {
    defaultDisputeDuration,
    defaultDate,
    defaultStatusValue,
    defaultStatusOption,
    defaultSearchByValue,
  } = getDefaultValuesAndOptions();
  const [date, setDate] = useState<Duration>(defaultDate);
  const [shouldShowDateRangePicker, setShowDateRangePicker] = useState(
    defaultDisputeDuration.value === CUSTOM,
  );
  const [selectedFilters, setSelectedFilters] = useState<SelectedFilterType>(
    getDefaultFilterSelectedOptions(),
  );
  const [status, setStatus] = useState(defaultStatusValue);
  const [searchByValue, setSearchByValue] = useState(defaultSearchByValue);
  const [isFilterModalOpen, setIsFilterModalOpen] = useState(false);
  const isMobile = useMobile();
  const isMediumDesktopAndMobile = useMobile(mobileBreakoints);
  const defaultFocusedInput = useRef<'startDate' | null>(null);
  const { disputeDurationOptions, statusOptions } = getOptions(isMobile);
  const numberOfMonths = isMediumDesktopAndMobile
    ? MOBILE_CALENDAR_NUMBER_OF_MONTHS
    : DESKTOP_CALENDAR_NUMBER_OF_MONTHS;

  const handleSearch = (newSearchParams = {}, paramType = '') => {
    let searchParams: SearchArgs;
    if (paramType === 'filter') {
      searchParams = {
        ...date,
        status,
        ...newSearchParams,
      };
    } else
      searchParams = {
        ...date,
        status,
        ...getValidFilterKeys(selectedFilters),
        ...newSearchParams,
      };
    if (searchByValue) {
      if (searchByValue.trim().startsWith('pay_')) {
        searchParams.payment_id = searchByValue.trim();
      } else {
        searchParams.id = searchByValue.trim();
      }
    }

    onSubmit(searchParams);
  };

  const onDatesChange = ({ from, to }) => {
    setDate({
      from: moment.unix(from).startOf('day').unix(),
      to: moment.unix(to).endOf('day').unix(),
    });
    handleSearch({ from, to });
  };

  const onDurationChange = ([{ title, value }]: Option[]) => {
    trackDurationFilter({ dateRange: title, pathname });
    if (value === CUSTOM) {
      defaultFocusedInput.current = 'startDate';
      setDate({
        from: getFromTime(LAST_7_DAYS).unix(),
        to: endOfDay.unix(),
      });
      setShowDateRangePicker(true);
      return;
    }
    setShowDateRangePicker(false);
    const from = getFromTime(value as DurationOption['value']).unix();
    const to = endOfDay.unix();
    setDate({
      from,
      to,
    });
    handleSearch({ from, to });
  };

  const onStatusChange = (selectedStatuses: Option[]) => {
    const status = getValue(selectedStatuses);
    setStatus(status);
    handleSearch({ status });
    trackStatusFilter({
      status: selectedStatuses[0].title,
      pathname,
    });
  };

  const onSearchByValueChange = ({ value = '' }: { value?: string }) => {
    setSearchByValue(value);
  };

  const onSearch = () => {
    trackSearchButton({
      searchBy: searchByOptionsMap[searchByValue.startsWith('pay_') ? 'payment_id' : 'id'],
      searchByValue,
      pathname,
    });
    handleSearch();
  };

  const onFilterApply = (selectedFilters: SelectedFilterType) => {
    setIsFilterModalOpen(false);
    setSelectedFilters(selectedFilters);
    const filters = getValidFilterKeys(selectedFilters);

    Object.values(selectedFilters).forEach((selectedFilter) => {
      selectedFilter.forEach((filter) => {
        track({
          objectName: `${filter} filter`,
          properties: { section: TransactionsPagesMap[pathname] },
        });
      });
    });

    handleSearch(filters, 'filter');
  };

  const tags = useMemo(() => getTags(selectedFilters), [selectedFilters]);

  const handleTagDismiss = ({ key, value }: { key: string; value: string }) => {
    setSelectedFilters((prev) => ({
      ...prev,
      [key]: selectedFilters[key].filter((filter) => filter !== value),
    }));

    const filters = getValidFilterKeys({
      ...selectedFilters,
      [key]: selectedFilters[key].filter((filter) => filter !== value),
    });

    handleSearch(filters, 'filter');
  };

  return (
    <Box marginTop="spacing.2">
      <StyledListFilter data-testid="dispute-filter">
        <StyledSubListFilter className="scrollable-tab-header">
          <StyledDateRangePicker>
            <Dropdown
              onChange={onDurationChange}
              options={disputeDurationOptions}
              defaultOptions={[defaultDisputeDuration]}
              isDisabled={loading}
              bottomSheetTitle={disputeDurationSectionName}
            />
            {shouldShowDateRangePicker && date.from && date.to ? (
              <div className="rzp-daterange-picker">
                <div className="daterange-container">
                  <SuspenseWithLoader>
                    <DateRangePicker
                      onDatesChange={onDatesChange}
                      startDate={moment.unix(date.from)}
                      endDate={moment.unix(date.to)}
                      disabled={loading}
                      numberOfMonths={numberOfMonths}
                      withPortal={isMobile}
                      defaultFocusedInput={defaultFocusedInput.current}
                    />
                  </SuspenseWithLoader>
                </div>
              </div>
            ) : null}
          </StyledDateRangePicker>
          <Dropdown
            onChange={onStatusChange}
            options={statusOptions}
            defaultOptions={[defaultStatusOption]}
            prefixTitle="Status: "
            isDisabled={loading}
            bottomSheetTitle={statusSectionName}
          />
          <Button
            variant="tertiary"
            icon={FilterIcon}
            onClick={() => setIsFilterModalOpen(true)}
            isDisabled={loading}
          >
            Filter
          </Button>
        </StyledSubListFilter>
        <StyledSearchByFilter>
          <Box display="flex" columnGap="spacing.1" marginLeft="auto">
            <TextInput
              showClearButton
              label=""
              defaultValue={defaultSearchByValue}
              placeholder="Payment ID or Dispute ID"
              onChange={onSearchByValueChange}
              isDisabled={loading}
              onClearButtonClick={() => onSearchByValueChange({})}
            />
            <Button
              accessibilityLabel="Search"
              icon={SearchIcon}
              size="medium"
              testID="search"
              isDisabled={loading}
              onClick={onSearch}
            />
          </Box>
        </StyledSearchByFilter>
      </StyledListFilter>
      {!isEmpty(tags) ? (
        <Box
          paddingTop="spacing.4"
          paddingBottom="spacing.4"
          display="flex"
          alignItems="center"
          flexWrap="wrap"
          gap="spacing.4"
        >
          {tags?.map((tag, index) => {
            return (
              <Tooltip
                content={tag.tagValue}
                placement="bottom"
                key={`${tag.filterValue}-${index}`}
              >
                <TooltipInteractiveWrapper>
                  <Tag
                    onDismiss={() =>
                      handleTagDismiss({ key: tag.tagTitle, value: tag.filterValue })
                    }
                    key={tag.value}
                    size="large"
                  >
                    {tag.tagValue}
                  </Tag>
                </TooltipInteractiveWrapper>
              </Tooltip>
            );
          })}
        </Box>
      ) : null}

      <DisputeFilter
        isOpen={isFilterModalOpen}
        selectedFilters={selectedFilters}
        onDismiss={() => setIsFilterModalOpen(false)}
        onFilterApply={onFilterApply}
      />
    </Box>
  );
};

export default withRouter(DisputeListFilter);
