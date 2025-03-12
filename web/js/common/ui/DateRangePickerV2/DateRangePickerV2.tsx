import moment from 'moment';
import React, { useState, useEffect } from 'react';
import { PowerSelect } from 'react-power-select';
import { isMobileDevice } from 'merchant/components/Home/data';
import lazy from 'merchant/routes/LazyLoader';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import ErrorBoundary, { Ranks } from 'common/new-ui/ErrorBoundary';
import { getStartDateFromDiff } from 'common/utils/rzp-utils';
import { withRouter } from 'common/deprecated/withRouter';
import type { RouteComponentProps, WithRouterProps } from 'common/deprecated/RouteComponentProps';
import qs from 'query-string';
import { validateUnixTimestamp } from 'merchant/views/Settlements/v3/utils/common';

const Drp = lazy(
  () => import(/* webpackChunkName: 'Drp' */ 'common/ui/Forms/DateRangePickerField'),
);

const customRangeText = 'Custom Range';
const customRangeVal = 0;

const customPreset = {
  name: customRangeText,
  value: customRangeVal,
};

export type PresetOption = { name: string; value: number; disabled?: boolean };

type Props = {
  onDatesChange: (from: moment.Moment, to: moment.Moment, preset: PresetOption) => void;
  selectedPreset: PresetOption;
  setSelectedPreset: (arg0: PresetOption) => void;
  presets: PresetOption[];
  hideCustomPreset?: boolean;
  minStartDate?: moment.Moment;
  renderCalendarInfo?: () => any;
  numberOfMonths?: number;
  horizontalMargin?: number;
  isOutsideRange?: (x: moment.Moment) => boolean;
  icon?: JSX.Element;
} & RouteComponentProps &
  WithRouterProps;

const DateRangePickerV2 = ({
  onDatesChange,
  selectedPreset,
  setSelectedPreset,
  hideCustomPreset,
  minStartDate,
  renderCalendarInfo = () => {},
  numberOfMonths = 2,
  horizontalMargin = 0,
  isOutsideRange,
  icon,
  ...props
}: Props) => {
  const [dates, setDates] = useState<{
    startDate: null | moment.Moment;
    endDate: moment.Moment;
  }>({ startDate: null, endDate: moment().local() });
  const [presets, setPresets] = useState<PresetOption[]>([]);

  const handleSetDates = (startDate, endDate, preset) => {
    startDate = startDate.startOf('day');
    endDate = endDate.endOf('day');

    setDates({ startDate, endDate });
    onDatesChange?.(startDate, endDate, preset);
  };

  useEffect(() => {
    if (selectedPreset.name !== customPreset.name) {
      const endDate = moment().local();
      const startDate = getStartDateFromDiff(selectedPreset.value, endDate);
      handleSetDates(startDate, endDate, selectedPreset);
    }
  }, [selectedPreset]);

  const onPresetChange = ({ option }) => {
    setSelectedPreset(option);
  };

  const handleDatesChange = ({ from, to }) => {
    const { startDate, endDate } = dates;

    if (from === startDate?.unix() && to === endDate.unix()) {
      return;
    }

    handleSetDates(moment(from * 1000), moment(to * 1000), customPreset);
    setSelectedPreset(customPreset);
  };

  const updatePresets = (presets) => {
    let startDate = dates.startDate;
    const endDate = dates.endDate;

    let updatedSelectedPreset = { ...selectedPreset };
    const presetsCopy = [...presets];

    if (!hideCustomPreset) {
      presetsCopy.push(customPreset);
    }

    updatedSelectedPreset = updatedSelectedPreset || presets[0];

    if (!startDate) {
      startDate = getStartDateFromDiff(updatedSelectedPreset.value, endDate);
    }

    setSelectedPreset(updatedSelectedPreset);
    setDates({ endDate, startDate });
    setPresets(presetsCopy);
  };

  useEffect(() => {
    updatePresets(props.presets);
  }, [props.presets]);

  useEffect(() => {
    // To pick from and to date params from query params on mount
    const queryParams = qs.parse(location.search);
    if (queryParams.from && queryParams.to) {
      const fromUnixTimestamp = validateUnixTimestamp(queryParams.from as string);
      const toUnixTimestamp = validateUnixTimestamp(queryParams.to as string);
      if (fromUnixTimestamp && toUnixTimestamp) {
        handleDatesChange({
          from: fromUnixTimestamp,
          to: toUnixTimestamp,
        });
        // set custom range as filter if query param has from and to on mount
        setSelectedPreset(customPreset);
      }
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const { startDate, endDate } = dates;
  return (
    <div className="rzp-daterange-picker clearfix">
      <div className="icon-container pull-left">
        {icon ? icon : <i className="i i-date-range" />}
      </div>
      <div className="presets-container pull-left" data-testid="date-range-presets">
        {presets.length > 0 && (
          <ErrorBoundary resetOnProps rank={Ranks.P2}>
            <PowerSelect
              className="date-range-preset-select react-normal-select"
              onChange={onPresetChange}
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
            onDatesChange={handleDatesChange}
            numberOfMonths={isMobileDevice() ? 1 : numberOfMonths}
            horizontalMargin={horizontalMargin}
            isOutsideRange={isOutsideRange ? isOutsideRange : (day) => moment().isBefore(day)}
            renderCalendarInfo={renderCalendarInfo}
          />
        </SuspenseWithLoader>
      </div>
    </div>
  );
};

export { customRangeText };
export default withRouter<Props>(DateRangePickerV2);
