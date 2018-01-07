import moment from 'moment';
import React, { Component } from 'react';
import { PowerSelect } from 'react-power-select';
import Drp from 'rzp/ui/Forms/DateRangePickerField';

import './styles.styl';

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

// TODO: to be moved to rzp/ui
class DateRangePicker extends Component {
  constructor(props) {
    super(props);

    const now = moment();

    let { presets, startDate, endDate = moment() } = props;

    if (!Array.isArray(presets)) {
      presets = defaultPresets;
    }

    presets = presets.map(preset => {
      const text = preset[0],
        rest = preset.slice(1),
        timeStampDiff = now.unix() - now.add(...rest).unix();

      return { name: text, value: timeStampDiff };
    });

    this.customPreset = {
      name: customRangeText,
      value: customRangeVal,
    };

    presets.push(this.customPreset);

    let selectedPreset = presets[props.defaultPreset || 0];
    startDate = getStartDateFromDiff(selectedPreset.value, endDate);

    this.state = {
      startDate,
      endDate,
      selectedPreset,
      presets,
    };

    this.onPresetChange = this.onPresetChange.bind(this);
    this.onDatesChange = this.onDatesChange.bind(this);
  }

  setDates(startDate, endDate) {
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

  componentWillReceiveProps(nextProps) {
    if (nextProps.presets !== this.props.presets) {
      this.setState({
        presets: nextProps.presets.concat([this.selectedPreset]),
      });
    }
  }

  render() {
    const { icon, onDatesChange } = this.props;

    let { presets, selectedPreset } = this.state;

    return (
      <div className="rzp-daterange-picker clearfix">
        <div className="icon-container pull-left">
          {!!icon ? icon : <i class="icon icon-date-range" />}
        </div>
        <div className="presets-container pull-left">
          <PowerSelect
            className="react-normal-select"
            onChange={this.onPresetChange}
            searchEnabled={false}
            optionLabelPath="name"
            selected={selectedPreset}
            options={presets}
          />
        </div>
        <div className="daterange-container pull-left">
          <Drp
            startDate={this.state.startDate}
            endDate={this.state.endDate}
            onDatesChange={this.onDatesChange}
          />
        </div>
      </div>
    );
  }
}

export default DateRangePicker;
