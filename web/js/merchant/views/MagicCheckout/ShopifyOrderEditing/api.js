import { merchantFetch } from 'merchant/utils/ajax';

export const fetchShopifyOrders = (skip = 0, count = 20, search_id = '') => {
  return merchantFetch({
    url: `1cc/magic/platform/orders/search?skip=${skip}&count=${count}&search_term=${search_id}&search_field=name`,
    method: 'get',
  });
};

export const fetchOrderDetails = (order_id) => {
  return merchantFetch({
    url: `1cc/magic/platform/order?order_id=${order_id}`,
    method: 'get',
  });
};

export const beginOrderEditing = (order_id) => {
  return merchantFetch({
    url: `1cc/magic/platform/order/edit/start?order_id=${order_id}`,
    method: 'post',
  });
};

export const addNewLineItem = (edit_id, data) => {
  return merchantFetch({
    url: `1cc/magic/platform/order/edit/lineitem/add?edit_id=${edit_id}`,
    method: 'post',
    data,
  });
};

export const searchLineItems = (limit, offset, search_term) => {
  return merchantFetch({
    url: `1cc/magic/platform/products/search?skip=${offset}&count=${limit}&search_term=${search_term}`,
    method: 'get',
  });
};

export const removeLineItem = (edit_id, edit_line_item_id) => {
  return merchantFetch({
    url: `1cc/magic/platform/order/edit/lineitem/quantity?edit_id=${edit_id}`,
    method: 'post',
    data: {
      quantity: 0,
      edit_line_item_id,
    },
  });
};

export const addCustomLineItem = (edit_id, payload) => {
  return merchantFetch({
    url: `1cc/magic/platform/order/edit/lineitem/custom?edit_id=${edit_id}`,
    method: 'post',
    data: {
      custom_item: {
        currency: 'INR',
        ...payload,
      },
    },
  });
};

export const addLineItemDiscount = (edit_id, data) => {
  return merchantFetch({
    url: `1cc/magic/platform/order/edit/discount/add?edit_id=${edit_id}`,
    method: 'post',
    data,
  });
};

export const removeLineItemDiscount = (edit_id, data) => {
  return merchantFetch({
    url: `1cc/magic/platform/order/edit/discount/remove?edit_id=${edit_id}`,
    method: 'post',
    data,
  });
};

export const editLineItemQuantity = (edit_id, data) => {
  return merchantFetch({
    url: `1cc/magic/platform/order/edit/lineitem/quantity?edit_id=${edit_id}`,
    method: 'post',
    data,
  });
};

export const commitOrderEditing = (edit_id, data) => {
  return merchantFetch({
    url: `1cc/magic/platform/order/edit/commit?edit_id=${edit_id}`,
    method: 'post',
    data,
  });
};
