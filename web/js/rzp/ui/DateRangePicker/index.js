import moment from 'moment';
import React, { Component } from 'react';
import { PowerSelect } from 'react-power-select';
import Drp from 'rzp/ui/Forms/DateRangePickerField';

const defaultPresets = [],
  customRangeText = 'Custom Range',
  customRangeVal = 0;

const getStartDateFromDiff = (diff, endDate) => {
  /*
   * @param {Number} diff
   * @param {Moment} endDate
   *
   * given , diff (seconds) and endDate , gives startDate
   */

  return moment(endDate.toDate() - diff * 1000);
};

class DateRangePicker extends Component {
  constructor(props) {
    super(props);

    let { presets, startDate, endDate = moment() } = props;

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

  setDates(startDate, endDate) {
    startDate = startDate.startOf('day');
    endDate = endDate.startOf('day');

    this.setState(
      {
        startDate,
        endDate,
      },
      () => {
        return (
          this.props.onDatesChange &&
          this.props.onDatesChange(startDate, endDate)
        );
      }
    );
  }

  onPresetChange({ option: selectedPreset }) {
    this.setState({ selectedPreset });

    if (selectedPreset !== this.customPreset) {
      let endDate = moment(),
        startDate = getStartDateFromDiff(selectedPreset.value, endDate);

      this.setDates(startDate, endDate);
    }
  }

  onDatesChange({ from, to }) {
    let startDate = this.state.startDate,
      endDate = this.state.endDate;

    if (from === startDate.unix() && to === endDate.unix()) {
      return;
    }

    this.setDates(moment(from * 1000), moment(to * 1000));

    this.setState({ selectedPreset: this.customPreset });
  }

  updatePresets(
    presets = this.props.presets,
    defaultPreset = this.props.defaultPreset
  ) {
    const now = moment();

    let { startDate, endDate } = this.state;

    let { selectedPreset } = this.state;

    presets = presets.map(preset => {
      const text = preset[0],
        rest = preset.slice(1),
        timeStampDiff =
          now.unix() -
          now
            .clone()
            .add(...rest)
            .unix();

      const result = { name: text, value: timeStampDiff };

      // powerSelect compares by reference
      if (selectedPreset && selectedPreset.name === text) {
        selectedPreset = result;
      }

      return result;
    });

    presets.push(this.customPreset);

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

  componentWillMount() {
    this.updatePresets();
  }

  componentWillReceiveProps(nextProps) {
    if (nextProps.presets !== this.props.presets) {
      this.updatePresets(nextProps.presets);
    }
  }

  render() {
    const { icon, onDatesChange } = this.props;

    let { presets, selectedPreset, startDate, endDate } = this.state;

    return (
      <div className="rzp-daterange-picker clearfix">
        <div className="icon-container pull-left">
          {!!icon ? icon : <i class="icon icon-date-range" />}
        </div>
        <div className="presets-container pull-left">
          {presets.length > 0 && (
            <PowerSelect
              className="react-normal-select"
              onChange={this.onPresetChange}
              searchEnabled={false}
              optionLabelPath="name"
              selected={selectedPreset}
              options={presets}
            />
          )}
        </div>
        <div className="daterange-container pull-left">
          <Drp
            startDate={startDate}
            endDate={endDate}
            onDatesChange={this.onDatesChange}
          />
        </div>
      </div>
    );
  }
}

export default DateRangePicker;
