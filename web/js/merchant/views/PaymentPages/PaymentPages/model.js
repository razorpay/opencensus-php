import { merchantFetch } from 'merchant/utils/ajax';
import { generateReportV2 } from 'merchant/reducers/reports';
import { decodeSensitiveFields } from 'common/utils/rzp-utils';

function pruneReqPayload(reqPayload) {
  if (reqPayload.amount) {
    reqPayload.amount *= 100;
  }

  reqPayload.expire_by && (reqPayload.expire_by = Math.floor(reqPayload.expire_by / 1000));

  if (reqPayload.title) {
    // It is required field. Safe check.
    reqPayload.title = reqPayload.title.trim();
  }
}

export function createPaymentPage(data, queryParams) {
  const reqPayload = { ...data };

  pruneReqPayload(reqPayload);

  return merchantFetch({
    url: 'payment_pages',
    method: 'post',
    data: reqPayload,
    params: queryParams,
    headers: {
      'content-Type': 'application/json',
    },
  });
}

export function editPaymentPageItem(id, data) {
  const reqPayload = { ...data };

  return merchantFetch({
    url: `payment_pages/payment_page_item/${id}`,
    method: 'patch',
    data: reqPayload,
    headers: {
      'content-type': 'application/json',
    },
  });
}
export function editStorefrontItem(id, data) {
  const reqPayload = { ...data };

  return merchantFetch({
    url: `stores/catalogs/${id}`,
    method: 'patch',
    data: reqPayload,
    headers: {
      'content-type': 'application/json',
    },
  });
}

export function editPaymentPage(id, data) {
  const reqPayload = { ...data };

  // In paymentpages v2, following 4 fields can also be edited via this API.
  pruneReqPayload(reqPayload);

  delete reqPayload.currency;

  return merchantFetch({
    url: `payment_pages/${id}`,
    method: 'patch',
    data: reqPayload,
    headers: {
      'content-type': 'application/json',
    },
  });
}
export function editStorefrontPage(id, data, isTransform = true) {
  const reqPayload = { ...data };

  // In paymentpages v2, following 4 fields can also be edited via this API.
  pruneReqPayload(reqPayload);

  delete reqPayload.currency;

  return merchantFetch({
    //@todo: [storefront] update API URL
    url: `stores/${id}`,
    method: 'patch',
    data: reqPayload,
    headers: {
      'content-type': 'application/json',
      'X-Razorpay-NCA-Transform': isTransform ? '1' : '0',
    },
  });
}

export function uploadImageInDescription(file, progressTracker) {
  const fd = new FormData();
  fd.append('images[0]', file);

  return merchantFetch({
    url: `payment_pages/images`,
    method: 'post',
    data: fd,
    onUploadProgress: progressTracker,
  });
}

export function fetchPaymentPageEntity(id) {
  return merchantFetch({
    url: `payment_pages/${id}/details`,
    params: {
      expand: ['user'],
    },
  });
}

export function fetchPendingPayments(id) {
  return merchantFetch({
    url: `payment_pages/${id}/pending_payments`,
  });
}

export function fetchStorefrontEntity(id, isTransform) {
  return merchantFetch({
    url: `stores/${id}`,
    headers: {
      'X-Razorpay-NCA-Transform': isTransform ? '1' : '0',
    },
  });
}

export function fetchPaymentPagesList(data, extraParams = {}) {
  return merchantFetch({
    url: 'payment_pages',
    data,
    ...extraParams,
  });
}

export const fetchStorefrontList = (data) => {
  return merchantFetch({
    url: 'stores',
    data,
    headers: {
      'X-Razorpay-NCA-Transform': '1',
    },
  });
};

export const fetchStorefrontPaymentsList = (id, data) => {
  return merchantFetch({
    url: `stores/${id}/payments`,
    method: 'get',
    data: decodeSensitiveFields(data),
    headers: {
      'X-Razorpay-NCA-Transform': '1',
    },
  });
};

export function fetchPaymentsListForPaymentPage(id) {
  return merchantFetch({
    url: 'payments',
    params: {
      payment_link_id: id,
      captured: 1,
      count: 5,
    },
  });
}

export function deactivatePaymentPage(id) {
  return merchantFetch({
    url: `payment_pages/${id}/deactivate`,
    method: 'patch',
  });
}
export function deactivateStorefront(id) {
  return merchantFetch({
    url: `stores/${id}/deactivate`,
    method: 'patch',
    headers: {
      'X-Razorpay-NCA-Transform': '1',
    },
  });
}

export function activatePaymentPage(id, data) {
  return merchantFetch({
    url: `payment_pages/${id}/activate`,
    method: 'patch',
    data,
    headers: {
      'content-type': 'application/json',
    },
  });
}

export function activateStorefront(id, data) {
  return merchantFetch({
    url: `stores/${id}/activate`,
    method: 'patch',
    data,
    headers: {
      'X-Razorpay-NCA-Transform': '1',
    },
  });
}

export function sendLink(id, data) {
  const reqPayload = {};

  data.email && (reqPayload.emails = [data.email]);
  data.contact && (reqPayload.contacts = [data.contact]);

  return merchantFetch({
    url: `payment_pages/${id}/notify`,
    method: 'post',
    data: reqPayload,
  });
}
export function exportReportCSV(
  user,
  paymentPageEntity,
  configId,
  saveLongPollInstances,
  extension,
) {
  if (!configId) {
    return '';
  }

  const entityCreatedAt = paymentPageEntity.created_at;

  const reqPayload = {
    config_id: configId,
    generated_by: user.current,
    start_time: entityCreatedAt - 1, // Duration here doesn't make sense (as per API). So, start and end time is ~same as entity created_at
    end_time: entityCreatedAt + 1,
    template_overrides: _prepareTemplate(paymentPageEntity, extension),
  };

  // Similar as in merchant_common/views/Reports/index.js
  return generateReportV2(reqPayload, true, null, saveLongPollInstances, false);
}

export function _prepareTemplate(paymentPageEntity, extension) {
  const UDF_SCHEMA = JSON.parse(paymentPageEntity.settings.udf_schema);
  const udfKeys = {};

  UDF_SCHEMA.forEach((udf) => {
    udfKeys[udf.name] = [`payments.notes.${udf.name}`];
  });

  const templateOverrides = {
    filters: {
      payment_links: {
        id: {
          op: 'IN',
          values: [paymentPageEntity.id.replace('pl_', '')], // pl_ is trimmed off
        },
      },
    },
    //name of column should be notes key
    //order of these column doesnt matter right now
    output_fields: Object.keys(udfKeys),
    fields_map: udfKeys,
    file_meta: {
      extension,
    },
  };

  return templateOverrides;
}

export function getPaymentSplitAmongstItems(orderId) {
  return merchantFetch({
    url: `orders/${orderId}/line_items`,
    headers: {
      'content-type': 'application/json',
    },
  });
}

export function getPaymentPageDetailsById(orderId) {
  return merchantFetch({
    url: `orders/${orderId}/product_details`,
    headers: {
      'content-type': 'application/json',
    },
  });
}

/**************/

export function setReceiptDetails(id, params) {
  return merchantFetch({
    url: `payment_pages/${id}/receipt`,
    method: 'post',
    data: params,
    headers: {
      'content-type': 'application/json',
    },
  });
}

export function getReceiptDetails(paymentId) {
  return merchantFetch({
    url: `payment_pages/${paymentId}/receipt`,
  });
}

export function sendReceipt(paymentId, receipt) {
  const reqPayload = {};

  if (receipt) {
    reqPayload.receipt = receipt;
  }

  return merchantFetch({
    url: `payment_pages/${paymentId}/send_receipt`,
    method: 'post',
    data: reqPayload,
    headers: {
      'content-type': 'application/json',
    },
  });
}

export function saveReceipt(paymentId, receipt) {
  const reqPayload = {};

  if (receipt) {
    reqPayload.receipt = receipt;
  }

  return merchantFetch({
    url: `payment_pages/${paymentId}/save_receipt`,
    method: 'post',
    data: reqPayload,
    headers: {
      'content-type': 'application/json',
    },
  });
}

export function fetchCustomDomain() {
  return merchantFetch({
    url: 'payment_pages/cds/domains',
  });
}

export function fetchCustomDomainAvailablePlans() {
  return merchantFetch({
    url: 'payment_pages/cds/plans',
  });
}

export function fetchCustomDomainCurrentPlan() {
  return merchantFetch({
    url: 'payment_pages/cds/plans/plan',
  });
}

export function getIfDomainAlreadyLinked(domain_name) {
  return merchantFetch({
    url: 'payment_pages/cds/domains/exists',
    data: { domain_name },
  });
}

export function getIfSubDomain(domain_name) {
  return merchantFetch({
    url: 'payment_pages/cds/subdomain',
    data: { domain_name },
  });
}

export function checkDNSPropogation(domain_name) {
  return merchantFetch({
    url: 'payment_pages/cds/propagation',
    data: { domain_name },
  });
}

export function createCustomDomainEntry(domain_name, plan_id) {
  return merchantFetch({
    url: 'payment_pages/cds/domains',
    method: 'post',
    data: { domain_name, plan_id },
  });
}

export function removeCustomDomainEntry(domain_name) {
  return merchantFetch({
    url: 'payment_pages/cds/domains',
    method: 'delete',
    data: { domain_name },
  });
}

export function fetchStorefrontCategories() {
  return merchantFetch({
    url: 'stores/categories?count=15&page=1',
    method: 'get',
  });
}

export function addStorefrontCategory(categoryName) {
  return merchantFetch({
    url: 'stores/categories',
    method: 'post',
    data: { name: categoryName },
  });
}

export function updateStorefrontCategory(categoryId, data) {
  return merchantFetch({
    url: `stores/categories/${categoryId}`,
    method: 'put',
    data,
  });
}

export function deleteStorefrontCategory(id) {
  return merchantFetch({
    url: `stores/categories/${id}`,
    method: 'delete',
  });
}

export function createProductCatalog(data) {
  return merchantFetch({
    url: 'stores/catalogs',
    method: 'post',
    data,
  });
}
export function fetchProductCatalogs(count = 10, isIncludeImagesCategories = true) {
  const params = {
    count,
  };
  if (isIncludeImagesCategories) {
    params.includes = 'Images,Categories';
  }
  // count is always present
  return merchantFetch({
    url: `stores/catalogs`,
    method: 'get',
    data: {
      ...params,
    },
  });
}

export function editProductCatalog(id, data) {
  return merchantFetch({
    url: `stores/catalogs/${id}`,
    method: 'patch',
    data,
  });
}

export function deleteProductCatalog(id) {
  return merchantFetch({
    url: `stores/catalogs/${id}`,
    method: 'delete',
  });
}

export function fetchAllCatalogDetails2(domain_name) {
  return merchantFetch({
    url: 'payment_pages/cds/domains',
    method: 'delete',
    data: { domain_name },
  });
}

export function createStorefront(data) {
  return merchantFetch({
    url: 'stores',
    method: 'post',
    data,
  });
}

export function editStorefront(id, data) {
  return merchantFetch({
    url: `stores/${id}`,
    method: 'patch',
    data,
  });
}

export function getStorefrontLineItems(orderId) {
  return merchantFetch({
    url: `stores/orders/${orderId}/line_items`,
    headers: {
      'content-type': 'application/json',
    },
  });
}
