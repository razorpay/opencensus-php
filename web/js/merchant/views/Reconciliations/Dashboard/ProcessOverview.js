import React from 'react';
import { Text, Box, Divider, Heading, ToastContainer } from '@razorpay/blade/components';
import ReconciledIcon from 'assets/reconciliations/reconciled.svg';
import UnreconciledIcon from 'assets/reconciliations/unreconciled.svg';
import moment from 'moment';
import { connect } from 'react-redux';
import { compose } from 'redux';

import DateRangePicker from 'common/ui/DateRangePicker';
import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import { formatAmount, getStartDateFromDiff } from 'common/utils/rzp-utils';
import { dateRangePresets } from 'merchant/views/Reconciliations/Dashboard/constants';
import { RenderErrorLoadingOrChild } from 'merchant/views/Reconciliations/commonComponents';
import { ReconScreens } from 'merchant/views/Reconciliations/const';
import { useReconTracking } from 'merchant/views/Reconciliations/hooks';
const ProcessDetail = ({ activeProcess, stats, currency, handleRangeChange, dateRange, error }) => {
  const end = dateRange?.endDate?.format('ll');
  const start = dateRange?.startDate?.format('ll');
  const handlePresetChange = ({ value }) => {
    const dateObj = {
      startDate: getStartDateFromDiff(value, moment()),
      endDate: moment().endOf('day'),
    };
    analyticsTrackWithUserInfo({
      screen: ReconScreens.ProcessOverview,
      objectName: 'recon date range',
      actionName: 'selected',
      properties: {
        startDate: dateObj.startDate.format('lll'),
        endDate: dateObj.endDate.format('lll'),
      },
    });
    handleRangeChange(dateObj);
  };
  useReconTracking({
    objectName: 'recon process overview',
    screen: ReconScreens.ProcessOverview,
    properties: {
      activeProcessId: activeProcess?.id,
      activeProcessName: activeProcess?.name,
      activeProcessType: activeProcess?.type,
    },
  });
  return (
    <Box paddingTop="spacing.4" testID="recon-overview-page">
      <Box display="flex" alignItems="center" marginBottom="spacing.8">
        <Text marginRight="spacing.4">Showing details for</Text>
        <Box>
          <div className="date-range-container">
            <DateRangePicker
              startDate={dateRange.startDate}
              endDate={dateRange.endDate}
              onClose={handleRangeChange}
              presets={dateRangePresets}
              allowSingleDaySelect
              onSelectPreset={handlePresetChange}
            />
          </div>
        </Box>
      </Box>
      <RenderErrorLoadingOrChild isError={error} isLoading={!stats?.stats}>
        <Box display="flex" justifyContent="space-between" paddingX="spacing.6" width="60%">
          <StatBox
            currency={currency}
            icon={ReconciledIcon}
            stats={stats?.stats?.reconciled}
            title="Reconciled"
          />
          <Divider orientation="vertical" />
          <StatBox
            currency={currency}
            icon={UnreconciledIcon}
            stats={stats?.stats?.unreconciled}
            title="Unreconciled"
          />
          <Divider orientation="vertical" />
          <Box paddingRight="spacing.11">
            <Text weight="semibold" marginBottom="spacing.2">
              Total Duration
            </Text>
            <Text>
              {start} to {end}
            </Text>
            <Text variant="caption" color="surface.text.gray.muted">
              Last Run on: {moment(activeProcess?.last_run * 1000).format('lll')}
            </Text>
          </Box>
        </Box>
      </RenderErrorLoadingOrChild>
      <ToastContainer />
    </Box>
  );
};
const StatBox = ({ icon, stats, title, currency }) => {
  return (
    <Box display="flex" alignItems="flex-start" marginBottom="spacing.4">
      {icon ? <img src={icon} width="20px" /> : null}
      <Box marginLeft="spacing.2" gap="spacing.2">
        <Text weight="semibold" marginBottom="spacing.2">
          {title}
        </Text>
        {stats?.share ? (
          <Heading weight="semibold" size="medium">
            {stats.share === 'NaN' ? 0 : stats.share}%
          </Heading>
        ) : null}
        <Text size="small" weight="semibold">
          {stats.count} records
        </Text>
        <Text color="surface.text.gray.muted">{formatAmount(stats.sum, true, currency)}</Text>
      </Box>
    </Box>
  );
};
export default compose(
  connect((state) => ({
    currency: state.session.user?.merchant?.currency,
  })),
)(ProcessDetail);
