import React from 'react';
import styled from 'styled-components';

import { HorizontalBar } from 'react-chartjs-2';

import { getFormattedCurrency } from 'merchant/views/Wallet/utils';

import type { UsageInterface } from 'merchant/views/Wallet/AccountDetail/containers/AccountBalanceContainer';

const Container = styled.div`
  width: 400px;
  height: 100px;
`;

const LimitUtilisationGraph = ({
  limitUsed,
  maxLimit,
  limitUnused,
}: UsageInterface): JSX.Element => (
  <Container data-testid="utilisation-graph">
    <HorizontalBar
      data={{
        labels: [''],
        datasets: [
          {
            barThickness: 12,
            backgroundColor: ['hsla(149, 99%, 35%, 1)'],
            label: 'Used',
            data: [limitUsed],
          },
          {
            barThickness: 12,
            backgroundColor: ['hsla(216, 19%, 89%, 1)'],
            label: 'Un-used',
            data: [limitUnused],
          },
        ],
      }}
      options={{
        tooltips: {
          callbacks: {
            title: () => `${Math.floor((limitUsed / maxLimit) * 100).toFixed(1)}% Used`,
            label: (item) => getFormattedCurrency(+item.xLabel),
          },
        },
        legend: {
          labels: {
            boxWidth: 12,
          },
          display: true,
          align: 'end',
          position: 'top',
        },
        scales: {
          scaleLabel: {
            padding: 0,
          },
          xAxes: [
            {
              offset: false,
              gridLines: {
                display: false,
              },
              stacked: true,
              ticks: {
                suggestedMin: 0,
                suggestedMax: maxLimit,
                min: 0,
                max: maxLimit,
                maxTicksLimit: 1,
                callback: function callback(value) {
                  return value === maxLimit
                    ? `Total Limit ${getFormattedCurrency(value, 'INR')}`
                    : getFormattedCurrency(+value, 'INR');
                },
              },
            },
          ],
          yAxes: [
            {
              gridLines: {
                display: false,
              },
              stacked: true,
            },
          ],
        },
      }}
    />
  </Container>
);
export default LimitUtilisationGraph;
