import { useEffect, useMemo, useState } from 'react';
import moment from 'moment';

import DateRangePicker from 'common/ui/DateRangePicker';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { useOrderAnalyticsContext } from 'merchant/views/MagicCheckout/OrderAnalytics/OrderAnalyticsContext';
import { TABS } from 'merchant/views/MagicCheckout/OrderAnalytics/constants/tabs';
import SummaryWidget from 'merchant/views/MagicCheckout/OrderAnalytics/widgets/Summary';
import { RCOD_APP_NAME, SOPC_APP_NAME } from 'merchant/views/MagicCheckout/common/constants';
import { ORG_NAME } from 'merchant/views/PartnerDashboard/constants';

// all ranges offseted by 1 since end date for those preset is day before
const DATE_RANGE_PRESETS = [
  ['Today', 0, 'day'],
  ['Past 2 days', -1, 'days'],
  ['Past 7 days', -6, 'days'],
  ['Past 30 days', -29, 'days'],
  ['Past 90 days', -89, 'days'],
];

const DEFAULT_PRESET = 1;
const TODAY = moment();
const DAY_BEFORE = moment().subtract('1', 'day');

const Header = (props) => {
  const { setTimeRange, setReportsTimeRange, updated_at, dashboardView, org } = props;
  const { activeTab } = useOrderAnalyticsContext();
  const isConversionTab = activeTab.label === TABS.CONVERSION.label;
  const isReportsTab = activeTab.label === TABS.REPORTS.label;
  const orgName = org?.business_name || ORG_NAME.RZP;

  const presetList = useMemo(
    () => (isConversionTab ? DATE_RANGE_PRESETS.slice(1) : DATE_RANGE_PRESETS),
    [isConversionTab],
  );

  const defaultPreset = useMemo(() => {
    const timeDiff = DAY_BEFORE.unix() - moment().subtract(2, 'days').unix();
    return { name: 'Custom Range', value: timeDiff };
  }, [activeTab]);

  const [selectedPreset, setSelectedPreset] = useState(defaultPreset);

  const endDate = useMemo(() => {
    if (selectedPreset?.name === 'Today') {
      return TODAY;
    } else {
      return DAY_BEFORE;
    }
  }, [selectedPreset]);

  const onDatesChange = (start, end) => {
    isReportsTab ? setReportsTimeRange({ start, end }) : setTimeRange({ start, end });
  };

  const handlePresetChange = (preset) => {
    setSelectedPreset(preset);
  };

  useEffect(() => {
    setSelectedPreset(defaultPreset);
  }, [defaultPreset, activeTab]);

  const isOutsideRange = (day) => {
    const defaults =
      day.isBefore(moment().subtract(91, 'days')) || day.isBefore(moment('2023-03-01'));
    if (isConversionTab || isReportsTab) {
      return defaults || day.isAfter(DAY_BEFORE);
    }
    return defaults || day.isAfter(TODAY);
  };

  const getHeaderText = () => {
    const productName =
      dashboardView === RCOD_APP_NAME || dashboardView === SOPC_APP_NAME ? 'MagicX' : 'Magic';
    return `This data is only for ${orgName} ${productName} processed orders`;
  };

  return (
    <div className="sticky-header dashboard-header">
      <div>
        {!isConversionTab && !isReportsTab ? (
          <>
            <SummaryWidget />
            <small>
              <span className="orders-subtext">
                <i className="i i-info-circle" />
                {getHeaderText()}
              </span>
            </small>
          </>
        ) : (
          <div>
            <span className="orders-subtext">
              <i className="i i-info-circle" />
              {getHeaderText()}
            </span>
          </div>
        )}
      </div>
      <div className="date-range-container">
        <DateRangePicker
          presets={presetList}
          defaultPreset={DEFAULT_PRESET}
          onDatesChange={onDatesChange}
          selectedPresetFromParent={selectedPreset}
          onSelectPreset={handlePresetChange}
          endDate={endDate}
          isOutsideRange={isOutsideRange}
          hasCustomEndDate
          hideCustomPreset
          allowSingleDaySelect
        />
        <div className="last-updated">
          <small>
            <i className="i i-info-circle">
              <Popover align="bottom" theme="dark">
                <PopoverBody>
                  <div>Data available post March 1, 2023</div>
                </PopoverBody>
              </Popover>
            </i>
            &nbsp;
            <span>Data last updated {moment.unix(updated_at).fromNow()} </span> <br />
          </small>
        </div>
      </div>
    </div>
  );
};

export default Header;
