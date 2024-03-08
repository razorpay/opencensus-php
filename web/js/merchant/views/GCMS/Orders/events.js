import { track } from 'merchant/views/GCMS/shared/analytics';

const OBJECT_NAMES = {
  ORDER: 'Order',
  ORDERS_ITEM: 'Orders Item',
  ORDERS_DETAILS: 'Orders Details',
  ORDERS_FILTER: 'Orders Filter',
  ORDERS_CREATE: 'Orders Create',
  ORDERS_CREATE_CART: 'Orders Create Cart',
  ORDERS_CREATE_PROGRAMS: 'Orders Create Programs',
  ORDERS_CREATE_PROGRAMS_CART: 'Orders Create Programs Cart',
  ORDERS_CREATE_PROGRAMS_MODAL: 'Orders Create Programs Modal',
};

export const trackOrdersPageLoadSuccess = () => {
  track({
    objectName: OBJECT_NAMES.ORDER,
    screen: 'OrdersList',
    actionName: 'Page Load Success',
  });
};

export const trackOrdersItemClicked = ({
  orderId,
  resellerId,
  orderTotalAamount,
  orderCreatedAt,
  orderUpdatedAt,
  orderStatus,
  deliveryStatus,
  isMultipleDelivery,
}) =>
  // eslint-disable-next-line max-params
  {
    track({
      objectName: OBJECT_NAMES.ORDERS_ITEM,
      screen: 'OrdersList',
      actionName: 'Clicked',
      properties: {
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
  };

export const trackOrdersDetailsPageLoadSuccess = ({
  orderId,
  resellerId,
  orderTotalAamount,
  orderCreatedAt,
  orderUpdatedAt,
  orderStatus,
  deliveryStatus,
  isMultipleDelivery,
}) =>
  // eslint-disable-next-line max-params
  {
    track({
      objectName: OBJECT_NAMES.ORDERS_DETAILS,
      screen: 'OrderDetails',
      actionName: 'Page Load Success',
      properties: {
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
  };

export const trackOrdersFiltersClicked = ({
  orderId,
  resellerName,
  durationStartDate,
  durationEndDate,
  status,
}) => {
  track({
    objectName: OBJECT_NAMES.ORDERS_FILTER,
    screen: 'OrdersFilters',
    actionName: 'Clicked',
    properties: {
      orderId,
      resellerName,
      durationStartDate,
      durationEndDate,
      status,
    },
  });
};

export const trackOrdersFiltersCleared = () => {
  track({
    objectName: OBJECT_NAMES.ORDERS_FILTER,
    screen: 'OrdersFilters',
    actionName: 'Cleared',
  });
};

export const trackOrdersCreateClicked = () => {
  track({
    objectName: OBJECT_NAMES.ORDERS_CREATE,
    screen: 'ResellersDetails',
    actionName: 'Clicked',
  });
};

export const trackOrdersCreateCartClicked = () => {
  track({
    objectName: OBJECT_NAMES.ORDERS_CREATE_CART,
    screen: 'ResellersDetails',
    actionName: 'Clicked',
  });
};

export const trackOrdersCreateCartProgramsClicked = ({ resellerId, orderId }) => {
  track({
    objectName: OBJECT_NAMES.ORDERS_CREATE_PROGRAMS,
    screen: 'OrdersCreatePrograms',
    actionName: 'Clicked',
    properties: {
      resellerId,
      orderId,
    },
  });
};

export const trackOrdersCreateCartProgramsPageLoadSuccess = ({ resellerId, orderId }) => {
  track({
    objectName: OBJECT_NAMES.ORDERS_CREATE_PROGRAMS,
    screen: 'OrdersCreatePrograms',
    actionName: 'Page Load Success',
    properties: {
      resellerId,
      orderId,
    },
  });
};

export const trackOrdersCreateCartProgramsCartClicked = ({ orderId, resellerId }) => {
  track({
    objectName: OBJECT_NAMES.ORDERS_CREATE_PROGRAMS_CART,
    screen: 'OrderFooterSection',
    actionName: 'Clicked',
    properties: {
      orderId,
      resellerId,
    },
  });
};

export const trackOrdersCreateCartProgramsModalPageLoadSuccess = ({
  type,
  programId,
  skuId,
  orderId,
  resellerId,
}) => {
  track({
    objectName: OBJECT_NAMES.ORDERS_CREATE_PROGRAMS_MODAL,
    screen: 'OrderCreateProgramDenominationsModal',
    actionName: 'Page Load Success',
    properties: {
      type,
      programId,
      skuId,
      orderId,
      resellerId,
    },
  });
};

export const trackOrdersCreateCartProgramsModalSuccess = ({
  type,
  programId,
  skuId,
  orderId,
  resellerId,
}) => {
  track({
    objectName: OBJECT_NAMES.ORDERS_CREATE_PROGRAMS_MODAL,
    screen: 'OrderCreateProgramDenominationsModal',
    actionName: 'Success',
    properties: {
      type,
      programId,
      skuId,
      orderId,
      resellerId,
    },
  });
};

export const trackOrdersCreateCartProgramsModalCancelled = ({
  type,
  programId,
  skuId,
  orderId,
  resellerId,
}) => {
  track({
    objectName: OBJECT_NAMES.ORDERS_CREATE_PROGRAMS_MODAL,
    screen: 'OrderCreateProgramDenominationsModal',
    actionName: 'Cancelled',
    properties: {
      type,
      programId,
      skuId,
      orderId,
      resellerId,
    },
  });
};

export const trackOrderCartPageLoadSuccess = ({ resellerId, orderId }) => {
  track({
    objectName: OBJECT_NAMES.ORDERS_CREATE_CART,
    screen: 'OrderCart',
    actionName: 'Page Load Success',
    properties: {
      resellerId,
      orderId,
    },
  });
};

export const trackOrderCartVerifySuccess = ({ resellerId, orderId }) => {
  track({
    objectName: OBJECT_NAMES.ORDERS_CREATE_CART,
    screen: 'OrderCart',
    actionName: 'Verify Success',
    properties: {
      resellerId,
      orderId,
    },
  });
};

export const trackOrderCartVerifyFailure = ({ resellerId, orderId }) => {
  track({
    objectName: OBJECT_NAMES.ORDERS_CREATE_CART,
    screen: 'OrderCart',
    actionName: 'Verify Failure',
    properties: {
      resellerId,
      orderId,
    },
  });
};
