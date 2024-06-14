import React from 'react';
import {
  Box,
  Dropdown,
  DropdownButton,
  DropdownOverlay,
  ActionList,
  ActionListItem,
  TextInput,
  Button,
  SearchIcon,
  ChevronDownIcon,
} from '@razorpay/blade/components';
import {
  All,
  AllFilters,
  BusinessModel,
  FilterDropDownActionType,
  FilterStateType,
  PosActivationStatus,
  FeeCategory,
  SearchBy,
  DeviceType,
} from '../../types';
import useFilter from '../../hooks/useFilter';
import useDevice from '../../hooks/useDevice';

type DropDownWrapperPropType = {
  placeholder: string;
  currentState: FilterDropDownActionType;
  onChange: (value: FilterDropDownActionType) => void;
  actionList: Array<FilterDropDownActionType>;
};

type FilterPropType = {
  onSearchClick: (filterState: FilterStateType) => void;
};

const DropDownWrapper: React.FC<DropDownWrapperPropType> = ({
  placeholder,
  currentState,
  onChange,
  actionList,
}) => {
  return (
    <Dropdown marginRight="spacing.3">
      <DropdownButton variant="tertiary" icon={ChevronDownIcon} iconPosition="right">{`${
        placeholder ? `${placeholder}:` : ''
      } ${currentState}`}</DropdownButton>
      <DropdownOverlay>
        <ActionList>
          {actionList.map((action) => (
            <ActionListItem
              onClick={() => onChange(action)}
              key={action}
              title={action.split('_').join('_')}
              value={action}
            />
          ))}
        </ActionList>
      </DropdownOverlay>
    </Dropdown>
  );
};

const Filter: React.FC<FilterPropType> = ({ onSearchClick }) => {
  const isMobile = useDevice(DeviceType.MOBILE);
  const [filterState, setFilterState] = useFilter();

  const onFilterStateChange = (name: AllFilters) => {
    return (value: FilterDropDownActionType) => {
      setFilterState(name, value);
    };
  };

  const onSearchChange = (value: string | undefined) => {
    setFilterState(AllFilters.SEARCH_FIELD, value ?? '');
  };

  return (
    <Box
      display="flex"
      flexDirection="row"
      justifyContent="space-between"
      width="100%"
      flexWrap="wrap-reverse"
    >
      <Box
        display="flex"
        flexDirection="row"
        justifyContent="space-between"
        width={isMobile ? '100%' : 'auto'}
        marginTop={isMobile ? 'spacing.5' : 'spacing.0'}
      >
        <DropDownWrapper
          placeholder="Status"
          currentState={filterState.status}
          onChange={onFilterStateChange(AllFilters.STATUS)}
          actionList={[...Object.values(All), ...Object.values(PosActivationStatus)]}
        />
        <DropDownWrapper
          placeholder="Pricing"
          currentState={filterState.pricing}
          onChange={onFilterStateChange(AllFilters.PRICING)}
          actionList={[...Object.values(All), ...Object.values(FeeCategory)]}
        />
        <DropDownWrapper
          placeholder="Business Model"
          currentState={filterState.businessModel}
          onChange={onFilterStateChange(AllFilters.BUSINESS_MODEL)}
          actionList={[...Object.values(All), ...Object.values(BusinessModel)]}
        />
      </Box>
      <Box
        display={isMobile ? 'grid' : 'flex'}
        flexDirection="row"
        justifyContent="space-between"
        width={isMobile ? '100%' : 'auto'}
        gridTemplateColumns={isMobile ? '1fr 4fr 0.5fr' : 'none'}
      >
        <DropDownWrapper
          placeholder=""
          currentState={filterState.searchBy}
          onChange={onFilterStateChange(AllFilters.SEARCH_BY)}
          actionList={[...Object.values(SearchBy)]}
        />
        <TextInput
          label=""
          value={filterState.searchField}
          placeholder={filterState.searchBy}
          alignSelf="center"
          type={filterState.searchBy === SearchBy.MOBILE_NUMBER ? 'telephone' : 'text'}
          onChange={({ value }) => onSearchChange(value)}
        />
        <Button icon={SearchIcon} variant="primary" onClick={() => onSearchClick(filterState)} />
      </Box>
    </Box>
  );
};

export default Filter;
