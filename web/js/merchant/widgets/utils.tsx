import React from 'react';
import { widgetKeyToComponentMapping } from 'merchant/widgets/mapping';
import { TrackParameters, renderWidgetProps } from './types';
import { ErrorState } from './common/ErrorState';
import { BoxProps } from '@razorpay/blade/components';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { QueryKey } from '@tanstack/react-query';

export const renderWidget = ({
  widget,
  isLoading = false,
  queryKey = [],
  widgetMapping,
  props,
  analyticsProperties = {},
}: renderWidgetProps & { widgetMapping: any }) => {
  const widgetComponent = widgetMapping[widget.type];
  if (widgetComponent) {
    // spread the props first, widget next (widget should not get overriding by props, but visa versa is ok)
    return widgetComponent({ ...props, ...widget, isLoading, queryKey, analyticsProperties });
  } else {
    return null;
  }
};

const backgroundImageMap = {
  productRecommendationBackground: 'product-recommendations.jpg',
  crossBorderPaymentBackground: 'cross-border-payment.jpg',
  qrCodeBackground: 'qr-code.jpg',
  paymentLinksBackground: 'payment-links.jpg',
  razorpayPosBackground: 'pos.jpg',
  payrollBackground: 'payroll.jpg',
  currentAccountBackground: 'current-account.jpg',
};

export const getBackgroundImage = (imageIdentifier) => {
  const isImageUrl = /^http/i.test(imageIdentifier);
  if (isImageUrl) return `url(${imageIdentifier})`;

  return `url(/img/rtux/${backgroundImageMap[imageIdentifier]})`;
};

export const getBaseWidget = ({ widget, isLoading, queryKey }: renderWidgetProps) =>
  renderWidget({ widget, isLoading, queryKey, widgetMapping: widgetKeyToComponentMapping });

export const ErrorBoundaryFallBackComponent = (props: BoxProps) => (
  <ErrorState
    borderWidth="thinner"
    borderColor="surface.border.gray.muted"
    backgroundColor="surface.background.gray.intense"
    borderRadius="large"
    marginX="spacing.0"
    {...props}
  />
);

export const UCS_SERVICE_NAME = 'UCS';

export const track = ({ objectName, actionName, screen, properties }: TrackParameters): void => {
  analyticsTrack({
    objectName: `${UCS_SERVICE_NAME} ${objectName}`,
    actionName,
    screen,
    properties: {
      ...(properties ? properties : {}),
      ...getCommonAnalyticsProperties(window.rzp_user, { addUserProperties: true }),
      version: 'v2',
      service: UCS_SERVICE_NAME.toLowerCase(),
      page: screen,
    },
  });
};

export const getUcsAliasFromQueryKey = (queryKey: QueryKey) => {
  if (queryKey.includes('rtux-homepage')) return 'home page';
  return '';
};
