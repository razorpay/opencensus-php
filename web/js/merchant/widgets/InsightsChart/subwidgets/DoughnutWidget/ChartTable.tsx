import { Amount, Box, Divider } from '@razorpay/blade/components';
import React, { Fragment } from 'react';
import {
  ChartDataType,
  ChartSchemaType,
} from 'merchant/widgets/InsightsChart/subwidgets/InsightItem/types';
import { ColorBox } from 'merchant/widgets/InsightsChart/subwidgets/DoughnutWidget/styled';
import { formatYAxis } from 'merchant/widgets/common/utils';
import { doughnutColors } from 'merchant/containers/Home/RTUX/colors';
import { titleCase } from 'common/utils/rzp-utils';

function ChartTable({ chartData }: { chartData: ChartDataType }) {
  const { data, schema } = chartData;
  const length = data[0].points.length;

  const getYValue = (point: number, schema: ChartSchemaType['y']) => {
    const formattedValue = formatYAxis(point, schema);
    if (schema.type !== 'amount') {
      return formattedValue;
    }

    return (
      <Amount
        value={formattedValue as number}
        currency={schema.unit as any}
        isAffixSubtle={false}
        suffix="none"
        type="body"
        size="medium"
        weight="semibold"
      />
    );
  };
  return (
    <Box maxWidth={{ base: '120px', m: '300px' }} flexGrow={1}>
      {data[0].points.map((point, index) => (
        <Fragment key={index}>
          <Box key={index}>
            <Box
              display="flex"
              justifyContent="space-between"
              alignItems="center"
              marginY="spacing.3"
            >
              <Box display="flex" alignItems="center" gap="spacing.3">
                <ColorBox backgroundColor={doughnutColors[index]} />
                <Box>{titleCase(point.x)}</Box>
              </Box>
              <Box>{getYValue(point.y, schema.y)}</Box>
            </Box>
          </Box>
          {index < length - 1 && <Divider />}
        </Fragment>
      ))}
    </Box>
  );
}

export default ChartTable;
