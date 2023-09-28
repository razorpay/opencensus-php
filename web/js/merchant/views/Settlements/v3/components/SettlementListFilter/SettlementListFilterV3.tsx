import ListFilter from 'merchant/components/ListFilter';
import DateRangePickerV2, { generatePresets } from 'common/ui/DateRangePickerV2';
import { Field, formValueSelector } from 'redux-form';
import ProviderSelector from 'merchant/components/ProviderSelector';
import React, { useState, useMemo, useEffect, useRef } from 'react';
import { StyledFilterDiv } from 'merchant/views/Settlements/v3/components/SettlementListFilter/styled';
import { classList, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { connect } from 'react-redux';
import { withRouter } from 'common/deprecated/withRouter';
import qs from 'query-string';
import { analyticsTrack } from 'common/utils/analytics';

const dateRangePresets: [string, number, string][] = [
  ['All Time', -30, 'days'],
  ['Past 7 Days', -7, 'days'],
  ['Past 30 Days', -30, 'days'],
  ['Past 90 Days', -90, 'days'],
];

export const presetsForCalendar = generatePresets(dateRangePresets);

const FORM_NAME = 'settlementsListFilter';
const allTimePresetName = dateRangePresets[0][0];
const getEmptyDate = () => ({ from: '', to: '' });

const SettlementListFilterV3 = ({
  terminalProviders,
  user,
  status,
  location,
  ...props
}): JSX.Element => {
  const [selectedPreset, setSelectedPreset] = useState({
    name: allTimePresetName,
    value: dateRangePresets[0][1],
  });
  const [date, setDate] = useState(getEmptyDate());

  const onDatesChange = (from, to) => {
    if (selectedPreset.name === allTimePresetName) {
      setDate(getEmptyDate());
    } else {
      setDate({
        from: from.unix(),
        to: to.unix(),
      });
    }
  };

  const [provider, setProvider] = useState({ name: 'All', value: '', gateway: '' });

  const onSelectPreset = (preset: typeof selectedPreset) => {
    if (preset.name === allTimePresetName) {
      setDate(getEmptyDate());
    }
    setSelectedPreset(preset);
  };

  const isAllTimeFilter = selectedPreset.name === allTimePresetName;

  useEffect(() => {
    // To pick from and to date params from query params on mount
    const queryParams = qs.parse(location.search);
    if (!(queryParams.from && queryParams.to) && !!queryParams.status) {
      setSelectedPreset(presetsForCalendar[2]);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const presetsToShow = useMemo(() => {
    // All time filter is not available for status all filter
    if (status && status !== 'all') {
      return presetsForCalendar.slice(1);
    }
    return presetsForCalendar;
  }, [status]);

  const onClear = () => {
    setSelectedPreset(presetsForCalendar[0]);
    props.onSubmit({});
  };

  const handleStatusOnChange = (e, value) => {
    // set Past 30 days as default filter when status is updated
    if (isAllTimeFilter && value !== 'all') {
      setSelectedPreset(presetsForCalendar[2]);
    }

    analyticsTrack({
      objectName: 'Settlement Status Drop down',
      actionName: 'Clicked',
      screen: 'Settlements',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        page: 'Home Screen',
        settlements_experiment_name: 'v2',
        sessionId: window?.session_id ? window.session_id : undefined,
      },
    });
    // status dropdown doesn't lose focus on selecting an option
    document.getElementById('status-dropdown')?.blur();
  };

  const statusFieldRef = useRef<HTMLSelectElement>(null);

  return (
    <StyledFilterDiv>
      <ListFilter
        date={date}
        provider={provider}
        setProvider={setProvider}
        additionalClass={classList(
          'settlements-v3-filter-group',
          isAllTimeFilter && 'all-time-filter-selected',
        )}
        form={FORM_NAME}
        resetHandler={onClear}
        maxMwebFiltersLength={1}
        isNewFilter
        {...props}
      >
        <div className="form-group datepicker-group">
          <label>Duration</label>
          {/* For m-web we want to show only one month to support mweb view */}
          <DateRangePickerV2
            presets={presetsToShow}
            setSelectedPreset={onSelectPreset}
            onDatesChange={onDatesChange}
            selectedPreset={selectedPreset}
          />
        </div>

        <div className="form-group list-filter-item">
          <label>UTR number</label>
          <Field name="utr" component="input" className="form-control input-sm" />
        </div>

        <div className="form-group list-filter-item">
          <label>Settlement ID</label>
          <Field name="id" component="input" className="form-control input-sm" />
        </div>

        {user.isSingleReconEnabled && user.isOptimizerEnabled && terminalProviders?.length > 0 && (
          <div className="form-group list-filter-item">
            <label>Payment Provider</label>
            <ProviderSelector
              name="provider"
              providers={terminalProviders}
              provider={provider}
              setProvider={setProvider}
            />
          </div>
        )}

        <div className="form-group list-filter-item">
          <label>Status</label>
          <Field
            name="status"
            component="select"
            className="form-control input-sm"
            onChange={handleStatusOnChange}
            ref={statusFieldRef}
            id="status-dropdown"
          >
            <option value="all">All</option>
            <option value="created">Created</option>
            <option value="processed">Processed</option>
            <option value="failed">Failed</option>
            <option value="initiated">Initiated</option>
          </Field>
        </div>
      </ListFilter>
    </StyledFilterDiv>
  );
};

const selector = formValueSelector(FORM_NAME); // <-- same as form name
const mapStateToProps = (store) => {
  return {
    status: selector(store, 'status'),
  };
};

export default withRouter<any>(connect(mapStateToProps, null)(SettlementListFilterV3));
