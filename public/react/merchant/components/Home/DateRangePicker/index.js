import moment from 'moment';
import React, { Component } from 'react';
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

class DateRangePicker extends Component {
  constructor(props) {
    super(props);

    const now = moment();

    let { presets, startDate, endDate = moment() } = props;

    if (!Array.isArray(presets)) {
      presets = defaultPresets;
    }

    this.presets = presets.map(preset => {
      const text = preset[0],
        rest = preset.slice(1),
        timeStampDiff = now.unix() - now.add(...rest).unix();

      return [text, timeStampDiff];
    });

    let selectedPreset = customRangeVal;

    if (this.presets.length > 0) {
      selectedPreset = this.presets[props.defaultPreset || 0][1];
      startDate = getStartDateFromDiff(selectedPreset, endDate);
    }

    this.state = {
      startDate,
      endDate,
      selectedPreset,
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

  onPresetChange(e) {
    const selectedPreset = Number(e.target.value);

    this.setState({ selectedPreset });

    if (selectedPreset !== customRangeVal) {
      let endDate = moment(),
        startDate = getStartDateFromDiff(selectedPreset, endDate);

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

    this.setState({ selectedPreset: customRangeVal });
  }

  render() {
    const { icon, startDate, endDate, onDatesChange } = this.props;

    let { presets } = this.props;

    return (
      <div className="rzp-daterange-picker">
        <div className="icon-container">
          {!!icon ? icon : <i class="icon icon-date-range" />}
        </div>
        <div className="presets-container">
          <select
            className="form-control input-sm"
            onChange={this.onPresetChange}
            value={this.state.selectedPreset}
          >
            {this.presets.map((preset, index) => {
              return (
                <option value={preset[1]} key={index}>
                  {preset[0]}
                </option>
              );
            })}
            <option value={customRangeVal}>Custom Range</option>
          </select>
        </div>
        <div className="daterange-container">
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
