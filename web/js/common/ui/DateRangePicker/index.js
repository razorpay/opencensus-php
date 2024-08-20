import moment from 'moment';
import React, { Component } from 'react';
import { PowerSelect } from 'react-power-select';
import { isMobileDevice } from 'merchant/components/Home/data';
import lazy from 'merchant/routes/LazyLoader';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import ErrorBoundary, { Ranks } from 'common/new-ui/ErrorBoundary';
import { getStartDateFromDiff } from 'common/utils/rzp-utils';

const Drp = lazy(() =>
  import(/* webpackChunkName: 'Drp' */ 'common/ui/Forms/DateRangePickerField'),
);

const defaultPresets = [];
const customRangeText = 'Custom Range';
const customRangeVal = 0;

class DateRangePicker extends Component {
  constructor(props) {
    super(props);

    let { presets } = props;
    const { endDate = moment().local() } = props;

    if (!Array.isArray(presets)) {
      presets = defaultPresets;
    }

    this.customPreset = {
      name: customRangeText,
      value: customRangeVal,
    };

    this.state = {
      startDate: null,
      endDate,
      selectedPreset: null,
      presets: [],
    };

    this.onPresetChange = this.onPresetChange.bind(this);
    this.onDatesChange = this.onDatesChange.bind(this);
  }

  setDates(startDate, endDate, preset) {
    startDate = startDate.startOf('day');
    endDate = endDate.endOf('day');

    this.setState(
      {
        startDate,
        endDate,
      },
      () => {
        return this.props.onDatesChange && this.props.onDatesChange(startDate, endDate, preset);
      },
    );
  }

  onPresetChange({ option: selectedPreset, updatedEndDate }) {
    this.setState({ selectedPreset });

    if (selectedPreset !== this.customPreset) {
      const endDate =
        this.props.hasCustomEndDate && updatedEndDate ? updatedEndDate : moment().local();
      const startDate = getStartDateFromDiff(selectedPreset.value, endDate);

      this.setDates(startDate, endDate, selectedPreset);

      if (this.props.onSelectPreset) {
        this.props.onSelectPreset(selectedPreset);
      }
    }
  }

  onDatesChange({ from, to }) {
    const startDate = this.state.startDate;
    const endDate = this.state.endDate;

    if (from === startDate.unix() && to === endDate.unix()) {
      return;
    }

    // this is required for calling onPresetChange when custom date range is selected
    if (this.props.callPresetChangeOnCustomOption && this.props.onSelectPreset) {
      this.props.onSelectPreset(this.customPreset);
    }

    this.setDates(moment(from * 1000), moment(to * 1000), this.customPreset);

    this.setState({ selectedPreset: this.customPreset });
  }

  updatePresets(presets = this.props.presets, defaultPreset = this.props.defaultPreset) {
    const now = moment();

    const { hideCustomPreset, minStartDate } = this.props;
    let { startDate } = this.state;
    const { endDate } = this.state;

    let { selectedPreset } = this.state;

    presets = presets.map((preset) => {
      const text = preset[0];
      const rest = preset.slice(1);
      const timeStampDiff =
        now.unix() -
        now
          .clone()
          .add(...rest)
          .unix();

      //to disable options if the timeStampDiff is before of the minStartDate
      const disabled = minStartDate
        ? getStartDateFromDiff(timeStampDiff, now) < minStartDate
        : false;

      const result = { name: text, value: timeStampDiff, disabled };

      // powerSelect compares by reference
      if (selectedPreset && selectedPreset.name === text) {
        selectedPreset = result;
      }

      return result;
    });

    if (!hideCustomPreset) {
      /**
       * we are removing , custom range option from select field as
       * from backend we are not supporting this feature , due to cost optim ization
       * We have to come up with new data lake architecture to provide this feature
       */
      // presets.push(this.customPreset);
    }

    selectedPreset = selectedPreset || presets[defaultPreset || 0];

    if (!startDate) {
      startDate = getStartDateFromDiff(selectedPreset.value, endDate);
    }

    this.setState({
      startDate,
      presets,
      selectedPreset,
    });
  }

  UNSAFE_componentWillMount() {
    this.updatePresets();
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    const { presets, selectedPresetFromParent } = this.props;

    if (nextProps.presets !== presets) {
      this.updatePresets(nextProps.presets);
    } else if (
      nextProps.selectedPresetFromParent &&
      nextProps.selectedPresetFromParent !== selectedPresetFromParent
    ) {
      /*this is required if the preset needs to be set from the parent component */
      this.onPresetChange({
        option: nextProps.selectedPresetFromParent,
        updatedEndDate: nextProps.endDate,
      });
    }
  }

  render() {
    const {
      icon,
      renderCalendarInfo = () => {},
      numberOfMonths = 2,
      horizontalMargin = 0,
      isOutsideRange,
      allowSingleDaySelect = false,
      onClose,
    } = this.props;

    const otherProps = {};

    if (allowSingleDaySelect) {
      otherProps.minimumNights = 0; // ref - https://github.com/react-dates/react-dates/issues/914
    }
    if (onClose) {
      otherProps.onClose = onClose;
    }

    const { presets, selectedPreset, startDate, endDate } = this.state;
    return (
      <div className="rzp-daterange-picker clearfix">
        <div className="icon-container pull-left">{icon ? icon : <i class="i i-date-range" />}</div>
        <div className="presets-container pull-left">
          {presets.length > 0 && (
            <ErrorBoundary resetOnProps rank={Ranks.P2}>
              <PowerSelect
                className="date-range-preset-select react-normal-select"
                onChange={this.onPresetChange}
                searchEnabled={false}
                optionLabelPath="name"
                selected={selectedPreset}
                options={presets}
              />
            </ErrorBoundary>
          )}
        </div>
        <div className="daterange-container pull-left">
          <SuspenseWithLoader>
            <Drp
              startDate={startDate}
              endDate={endDate}
              onDatesChange={this.onDatesChange}
              numberOfMonths={isMobileDevice() ? 1 : numberOfMonths}
              horizontalMargin={horizontalMargin}
              isOutsideRange={isOutsideRange ? isOutsideRange : (day) => moment().isBefore(day)}
              renderCalendarInfo={renderCalendarInfo}
              {...otherProps}
            />
          </SuspenseWithLoader>
        </div>
      </div>
    );
  }
}

export { customRangeText };
export default DateRangePicker;
