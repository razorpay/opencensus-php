import React, { Fragment } from 'react';
import ChartArea from './ChartArea';
import { Card, CardHeader, Dot, GraphLabel, GraphSection, LineChartArea } from './styled';
import LoadingError from './LoadingError';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import { GraphProps } from 'merchant/views/PaymentMetrics/types';

const Graph = ({
  title,
  description,
  interval,
  data,
  xAxisID,
  xLabel,
  yAxisID,
  yLabel,
  isLoading,
  noData,
  error,
  btnAction = undefined,
}: GraphProps): React.ReactElement => {
  return (
    <Card>
      <CardHeader>
        {title && <p>{title}</p>}
        {description && <p>{description}</p>}
      </CardHeader>
      {isLoading || noData || error ? (
        <LoadingError isLoading={isLoading} noData={noData} error={error} />
      ) : (
        <GraphSection>
          <LineChartArea>
            <ErrorBoundary
              FallbackComponent={() => (
                <LoadingError error="There was an issue, please try later!" />
              )}
              resetOnProps
            >
              <ChartArea
                interval={interval}
                data={data}
                xLabel={xLabel}
                yLabel={yLabel}
                xAxisID={xAxisID}
                yAxisID={yAxisID}
                btnAction={btnAction}
              />
            </ErrorBoundary>
          </LineChartArea>
          <GraphLabel>
            {data.datasets.map(({ label, backgroundColor }) => (
              <Fragment key={label}>
                <Dot backgroundColor={backgroundColor} />
                {label}
              </Fragment>
            ))}
          </GraphLabel>
        </GraphSection>
      )}
    </Card>
  );
};

export default Graph;
