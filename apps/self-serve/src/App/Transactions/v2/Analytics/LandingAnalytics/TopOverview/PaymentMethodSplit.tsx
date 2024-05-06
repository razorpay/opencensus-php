import React, { useRef, useState } from 'react';
import { Box, Card, CardBody, Text } from '@razorpay/blade/components';
import { isMobileDevice } from '@dashboard/shared-utils/home-utils';
import { getPercentage } from '@dashboard/shared-utils/rzp-utils';
import { Doughnut } from 'react-chartjs-2';
import {
  doughnutChartColors,
  getPaymentMethodData,
  getPaymentMethodLabel,
} from 'apps/self-serve/src/App/Transactions/v2/Analytics/utils';
import {
  PaymentMethodSplitProps,
  SuccessRateBannerSection,
} from 'apps/self-serve/src/App/Transactions/v2/Analytics/types';
import SuccessRateBanner from 'apps/self-serve/src/App/Transactions/v2/Analytics/components/SuccessRateBanner';
import { LegendDot } from 'apps/self-serve/src/App/Transactions/v2/Analytics/styled';
import { track } from 'apps/self-serve/src/App/Transactions/v2/common/tracking';

const PaymentMethodSplit = ({
  paymentByMethod,
  isMobile,
  shouldShowSrBanner,
  successRateData,
  durationOption,
}: PaymentMethodSplitProps): JSX.Element => {
  const shouldCollapseSrBanner = isMobileDevice(1200);
  const chartRef = useRef(null);
  const [hoveredSegment, setHoveredSegment] = useState<number>(-1);
  const { labels, segmentData, segmentDataTotal } = getPaymentMethodData(paymentByMethod);
  const data = {
    labels,
    datasets: [
      {
        data: segmentData,
        backgroundColor: doughnutChartColors,
      },
    ],
  };

  const chartOptions = {
    cutoutPercentage: 60,
    tooltips: {
      enabled: true,
      displayColors: false,
      mode: 'point',
      callbacks: {
        title: (tooltipItem: any) => segmentData[tooltipItem[0].index].toLocaleString('en-IN'),
        label: (tooltipItem: any) =>
          `${labels[tooltipItem?.index]} (${getPercentage(
            segmentDataTotal,
            segmentData[tooltipItem?.index],
          )}%)`,
      },
    },
    onHover: (_e: any, elements: { _index: any }[]) => {
      const nowHoveredElement = elements[0]?._index;
      if (!isNaN(nowHoveredElement) && hoveredSegment !== nowHoveredElement) {
        setHoveredSegment(nowHoveredElement);
        track({
          objectName: 'Payment Method Pie',
          actionName: 'Hovered',
          properties: {
            section: 'Overview',
            pieElementHovered: labels[nowHoveredElement],
            overviewDate: durationOption?.title,
          },
        });
      }
    },
  };

  return (
    <Card
      padding="spacing.3"
      marginY="spacing.5"
      backgroundColor="surface.background.gray.moderate"
      elevation="none"
      display="flex"
      testID="payment-method-split"
    >
      <CardBody>
        <Box
          display="flex"
          padding="spacing.4"
          paddingLeft={isMobile ? 'spacing.2' : 'spacing.4'}
          gap="spacing.2"
          flexDirection="column"
          minWidth="280px"
          height="200px"
          justifyContent="space-between"
        >
          <Box
            display="flex"
            flexDirection={shouldCollapseSrBanner ? 'column' : 'row'}
            justifyContent={isMobile ? '' : 'space-between'}
            gap="spacing.2"
          >
            <Text weight="semibold" size="medium" color="surface.text.gray.subtle">
              Split by payment method
            </Text>
            {shouldShowSrBanner ? (
              <SuccessRateBanner
                successRateData={successRateData}
                section={SuccessRateBannerSection.PAYMENTS_OVERVIEW}
              />
            ) : null}
          </Box>
          <Box
            display="flex"
            flexDirection="row"
            justifyContent="space-evenly"
            alignItems="center"
            gap="spacing.5"
          >
            <Box height="135px" width="135px">
              {/* @ts-expect-error expect error */}
              <Doughnut ref={chartRef} options={chartOptions} data={data} />
            </Box>
            <Box
              display="flex"
              flexDirection="column"
              justifyContent="space-between"
              gap="spacing.2"
            >
              {paymentByMethod.map(({ label, value }, index) => (
                <Box
                  key={index}
                  display="flex"
                  flexDirection="row"
                  justifyContent="space-between"
                  alignItems="center"
                  gap={shouldCollapseSrBanner ? 'spacing.5' : 'spacing.10'}
                >
                  <Box display="flex" flexDirection="row" alignItems="center" gap="spacing.2">
                    <LegendDot color={doughnutChartColors[index]} />
                    <Text size={isMobile ? 'small' : 'medium'} color="surface.text.gray.subtle">
                      {getPaymentMethodLabel(label)}
                    </Text>
                  </Box>
                  <Text size={isMobile ? 'small' : 'medium'} color="surface.text.gray.subtle">
                    {getPercentage(segmentDataTotal, value)}%
                  </Text>
                </Box>
              ))}
            </Box>
          </Box>
        </Box>
      </CardBody>
    </Card>
  );
};

export default PaymentMethodSplit;
