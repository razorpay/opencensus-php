import { merchantFetch } from 'merchant/utils/ajax';
import { snakeToTitleCase } from 'common/util';

function pruneReqPayload(reqPayload) {
  if (reqPayload.amount) {
    reqPayload.amount *= 100;
  }

  reqPayload.expire_by &&
    (reqPayload.expire_by = Math.floor(reqPayload.expire_by / 1000));

  if (reqPayload.title) {
    // It is required field. Safe check.
    reqPayload.title = reqPayload.title.trim();
  }
}

export function createPaymentPage(data) {
  const reqPayload = { ...data };

  pruneReqPayload(reqPayload);

  return merchantFetch({
    url: 'payment_links',
    method: 'post',
    data: reqPayload,
    headers: {
      'content-Type': 'application/json',
    },
  });
}

export function editPaymentPageItem(id, data) {
  const reqPayload = { ...data };

  return merchantFetch({
    url: `payment_links/payment_page_item/${id}`,
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
    url: `payment_links/${id}`,
    method: 'patch',
    data: reqPayload,
    headers: {
      'content-type': 'application/json',
    },
  });
}

export function uploadImageInDescription(file) {
  const fd = new FormData();
  fd.append('images[0]', file);

  return merchantFetch({
    url: `payment_links/images`,
    method: 'post',
    data: fd,
  });
}

export function fetchPaymentPageEntity(id) {
  return merchantFetch({
    url: `payment_links/${id}/details`,
    params: {
      expand: ['user'],
    },
  });
}

export function fetchPaymentPagesList(data) {
  return merchantFetch({
    url: 'payment_links',
    data,
  });
}

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
    url: `payment_links/${id}/deactivate`,
    method: 'patch',
  });
}

export function activatePaymentPage(id, data) {
  return merchantFetch({
    url: `payment_links/${id}/activate`,
    method: 'patch',
    data,
    headers: {
      'content-type': 'application/json',
    },
  });
}

export function sendLink(id, data) {
  const reqPayload = {};

  data.email && (reqPayload.emails = [data.email]);
  data.contact && (reqPayload.contacts = [data.contact]);

  return merchantFetch({
    url: `payment_links/${id}/notify`,
    method: 'post',
    data: reqPayload,
  });
}

export function exportReportCSV(user, paymentPageEntity, configId) {
  if (!configId) {
    return;
  }

  const reqPayload = {
    config_id: configId,
    generated_by: user.current,
    start_time: 1569938247, // Any random time before deployment of this feature
    end_time: new Date().getTime() / 1000, // Current time
    templateoverride: prepareTemplate(paymentPageEntity),
  };

  return merchantFetch({
    url: 'reporting/logs',
    method: 'post',
    data: reqPayload,
  });
}

export function prepareTemplate(paymentPageEntity) {
  const UDF_SCHEMA = JSON.parse(paymentPageEntity.settings.udf_schema);
  const udfKeys = {};

  UDF_SCHEMA.forEach(udf => {
    udfKeys[udf.name] = [snakeToTitleCase(udf.name)];
  });

  const templateoverride = {
    filters: {
      payment_links: {
        id: {
          op: 'IN',
          values: [paymentPageEntity.id],
        },
      },
    },
    //name of column should be notes key
    //order of these column doesnt matter right now
    output_fields: Object.keys(udfKeys),
    fields_map: udfKeys,
  };

  return templateoverride;
}
