import React from 'react';
import { ArrowRightIcon, Box, Link } from '@razorpay/blade/components';

import LineChart from './LineChart';
import { TabProps } from './types';
import { track } from 'merchant/widgets/utils';

const Tab: React.FC<TabProps> = ({ tabData, date, analyticsProperties }) => {
  const chartData = tabData.data.chart_data;
  const actionData = tabData.action;
  const { screen, ...rest } = analyticsProperties;

  const subWidgetId = `${analyticsProperties.widgetId}.${tabData.type}.${tabData.id}`;

  const trackTabClick = () =>
    track({
      objectName: `link`,
      actionName: 'clicked',
      screen,
      properties: {
        ...rest,
        subWidgetId,
        actionBy: subWidgetId,
        title: tabData.title,
        action: actionData.action,
        actionLabel: actionData.title,
        date,
      },
    });

  return (
    <Box
      position="relative"
      marginX="spacing.7"
      height="360px"
      testID={`chartjs-wrapper-${tabData.id}`}
    >
      <LineChart chartData={chartData} unit={date} />
      {actionData ? (
        <Link
          position="absolute"
          bottom="spacing.2"
          right="spacing.0"
          href={actionData.action}
          target="_blank"
          rel="noopener noreferer"
          icon={ArrowRightIcon}
          iconPosition={actionData.icon_position}
          onClick={trackTabClick}
        >
          {actionData.title}
        </Link>
      ) : null}
    </Box>
  );
};

export default Tab;
