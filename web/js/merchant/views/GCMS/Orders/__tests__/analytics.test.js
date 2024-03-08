import * as analytics from 'common/utils/analytics';
import {
  trackOrdersPageLoadSuccess,
  trackOrdersItemClicked,
  trackOrdersDetailsPageLoadSuccess,
  trackOrdersFiltersClicked,
  trackOrdersFiltersCleared,
  trackOrdersCreateClicked,
  trackOrdersCreateCartClicked,
  trackOrdersCreateCartProgramsClicked,
  trackOrdersCreateCartProgramsPageLoadSuccess,
  trackOrdersCreateCartProgramsCartClicked,
  trackOrderCartPageLoadSuccess,
  trackOrderCartVerifySuccess,
  trackOrderCartVerifyFailure,
} from 'merchant/views/GCMS/Orders/events';

import { orderItemsResponse } from './mocks/fixtures';
const trackSpy = jest.spyOn(analytics, 'analyticsTrack');

const commonProperties = {
  properties: {},
};

describe('Tests for analytics functions', () => {
  const sectionProperties = {
    location: 'GCMS',
    sessionId: 'not available',
    validity: 'not available',
  };
  const order = orderItemsResponse.data.order_items[0];
  const orderId = order?.id;
  const resellerId = order?.reseller_id;
  const orderTotalAamount = order?.total_amount;
  const orderCreatedAt = order?.created_at;
  const orderUpdatedAt = order?.updated_at;
  const orderStatus = order?.status;
  const deliveryStatus = order?.delivery_status;
  const isMultipleDelivery = order?.is_multiple_delivery;
  const status = order?.status;

  const durationStartDate = 1706711738;
  const durationEndDate = 1706711738;
  const resellerName = 'Ibaco';

  test('should call trackOrdersPageLoadSuccess with correct parameters', () => {
    trackOrdersPageLoadSuccess();

    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'Order',
      screen: 'OrdersList',
      actionName: 'Page Load Success',
      properties: {
        ...sectionProperties,
      },
    });
  });
  test('should call trackOrdersDetailsPageLoadSuccess with correct parameters', () => {
    trackOrdersDetailsPageLoadSuccess({
      orderId,
      resellerId,
      orderTotalAamount,
      orderCreatedAt,
      orderUpdatedAt,
      orderStatus,
      deliveryStatus,
      isMultipleDelivery,
    });

    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'Orders Details',
      screen: 'OrderDetails',
      actionName: 'Page Load Success',
      properties: {
        ...sectionProperties,
        orderId,
        resellerId,
        orderTotalAamount,
        orderCreatedAt,
        orderUpdatedAt,
        orderStatus,
        deliveryStatus,
        isMultipleDelivery,
      },
    });
  });
  test('should call trackOrdersItemClicked with correct parameters', () => {
    trackOrdersItemClicked({
      orderId,
      resellerId,
      orderTotalAamount,
      orderCreatedAt,
      orderUpdatedAt,
      orderStatus,
      deliveryStatus,
      isMultipleDelivery,
    });

    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'Orders Item',
      screen: 'OrdersList',
      actionName: 'Clicked',
      properties: {
        ...sectionProperties,
        orderId,
        resellerId,
        orderTotalAamount,
        orderCreatedAt,
        orderUpdatedAt,
        orderStatus,
        deliveryStatus,
        isMultipleDelivery,
      },
    });
  });
  test('should call trackOrdersFiltersClicked with correct parameters', () => {
    trackOrdersFiltersClicked({
      orderId,
      resellerName,
      durationStartDate,
      durationEndDate,
      status,
    });

    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'Orders Filter',
      screen: 'OrdersFilters',
      actionName: 'Clicked',
      properties: {
        ...sectionProperties,
        orderId,
        resellerName,
        durationStartDate,
        durationEndDate,
        status,
      },
    });
  });
  test('should call trackOrdersFiltersCleared with correct parameters', () => {
    trackOrdersFiltersCleared();

    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'Orders Filter',
      screen: 'OrdersFilters',
      actionName: 'Cleared',
      properties: {
        ...sectionProperties,
      },
    });
  });
  test('should call trackOrdersCreateClicked with correct parameters', () => {
    trackOrdersCreateClicked();

    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'Orders Create',
      screen: 'ResellersDetails',
      actionName: 'Clicked',
      properties: {
        ...sectionProperties,
      },
    });
  });
  test('should call trackOrdersCreateCartClicked with correct parameters', () => {
    trackOrdersCreateCartClicked();

    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'Orders Create Cart',
      screen: 'ResellersDetails',
      actionName: 'Clicked',
      properties: {
        ...sectionProperties,
      },
    });
  });
  test('should call trackOrdersCreateCartProgramsClicked with correct parameters', () => {
    trackOrdersCreateCartProgramsClicked({ resellerId, orderId });

    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'Orders Create Programs',
      screen: 'OrdersCreatePrograms',
      actionName: 'Clicked',
      properties: {
        ...sectionProperties,
        resellerId,
        orderId,
      },
    });
  });
  test('should call trackOrdersCreateCartProgramsPageLoadSuccess with correct parameters', () => {
    trackOrdersCreateCartProgramsPageLoadSuccess({ resellerId, orderId });

    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'Orders Create Programs',
      screen: 'OrdersCreatePrograms',
      actionName: 'Page Load Success',
      properties: {
        ...sectionProperties,
        resellerId,
        orderId,
      },
    });
  });
  test('should call trackOrdersCreateCartProgramsCartClicked with correct parameters', () => {
    trackOrdersCreateCartProgramsCartClicked({ resellerId, orderId });

    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'Orders Create Programs Cart',
      screen: 'OrderFooterSection',
      actionName: 'Clicked',
      properties: {
        ...sectionProperties,
        resellerId,
        orderId,
      },
    });
  });
  test('should call trackOrderCartPageLoadSuccess with correct parameters', () => {
    trackOrderCartPageLoadSuccess({ resellerId, orderId });

    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'Orders Create Cart',
      screen: 'OrderCart',
      actionName: 'Page Load Success',
      properties: {
        ...sectionProperties,
        resellerId,
        orderId,
      },
    });
  });
  test('should call trackOrderCartVerifySuccess with correct parameters', () => {
    trackOrderCartVerifySuccess({ resellerId, orderId });

    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'Orders Create Cart',
      screen: 'OrderCart',
      actionName: 'Verify Success',
      properties: {
        ...sectionProperties,
        resellerId,
        orderId,
      },
    });
  });
  test('should call trackOrderCartVerifyFailure with correct parameters', () => {
    trackOrderCartVerifyFailure({ resellerId, orderId });

    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'Orders Create Cart',
      screen: 'OrderCart',
      actionName: 'Verify Failure',
      properties: {
        ...sectionProperties,
        resellerId,
        orderId,
      },
    });
  });
});
