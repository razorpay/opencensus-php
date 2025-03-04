import React from 'react';
import moment from 'moment';

import { widgetKeyToComponentMapping } from 'merchant/widgets/mapping';
import { TrackParameters, renderWidgetProps } from './types';
import { ErrorState } from './common/ErrorState';
import { BoxProps } from '@razorpay/blade/components';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { QueryKey } from '@tanstack/react-query';
import { ValueOf } from 'merchant/views/Affordability/AssistedFinancing/type';
import { ANALYTICS } from 'common/constant';
import { durationOptionKeys } from './common/types';

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
  rizeBackground: 'rize.jpg',
  incorporateBackground: {
    base: 'incorporate-base.jpg',
    s: 'incorporate-s.jpg',
  },
  receiveGlobalBankBackground: 'global-bank.png',
};

export const getBackgroundImage = (imageIdentifier?: string): BoxProps['backgroundImage'] => {
  if (!imageIdentifier) return undefined;
  const rtuxCdnPath = `${window.cdnBaseUrl}/static/assets/rtux`;
  const isImageUrl = /^http/i.test(imageIdentifier);
  if (isImageUrl) return `url(${imageIdentifier})`;

  const image = backgroundImageMap[imageIdentifier] as
    | ValueOf<typeof backgroundImageMap>
    | undefined;
  if (!image) return undefined;
  if (typeof image === 'string') return `url(${rtuxCdnPath}/${image})`;

  const responsiveImage = {};
  Object.entries(image).forEach(([size, image]) => {
    responsiveImage[size] = /^http/i.test(image) ? `url(${image})` : `url(${rtuxCdnPath}/${image})`;
  });
  return responsiveImage;
};

export const getBaseWidget = ({ widget, isLoading, queryKey }: renderWidgetProps) => {
  return renderWidget({ widget, isLoading, queryKey, widgetMapping: widgetKeyToComponentMapping });
};

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

export const track = ({
  objectName,
  actionName,
  screen = ANALYTICS.SCREEN.DASHBOARD,
  properties,
}: TrackParameters): void => {
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

export const getDateRangeValues = (
  option: string,
  range?: { from: number; to: number },
): [Date, Date] => {
  if (range) {
    return [moment.unix(range.from).toDate(), moment.unix(range.to).toDate()];
  }
  switch (option) {
    case durationOptionKeys.TODAY:
      return [moment().toDate(), moment().toDate()];
    case durationOptionKeys.LAST_7_DAYS:
      return [moment().subtract(7, 'days').toDate(), moment().subtract(1, 'day').toDate()];
    case durationOptionKeys.LAST_30_DAYS:
      return [moment().subtract(30, 'days').toDate(), moment().subtract(1, 'day').toDate()];
    default:
      return [moment().subtract(7, 'days').toDate(), moment().subtract(1, 'day').toDate()];
  }
};
