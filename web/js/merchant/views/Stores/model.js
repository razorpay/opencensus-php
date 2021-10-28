import { merchantFetch } from 'merchant/utils/ajax';

export function uploadProductImage(file) {
  const fd = new FormData();
  fd.append('images[0]', file);

  return merchantFetch({
    url: `store/upload_image`,
    method: 'post',
    data: fd,
  });
}

export function sendLink(id, data) {
  const reqPayload = {};

  if (data.email) reqPayload.emails = [data.email];
  if (data.contact) reqPayload.contacts = [data.contact];

  // TODO: to be changed for stores, reusing PP share
  return merchantFetch({
    url: `payment_pages/${id}/notify`,
    method: 'post',
    data: reqPayload,
  });
}

export function fetchStore() {
  return merchantFetch({
    url: 'store',
    method: 'get',
  });
}

export function createStoreEntity(data) {
  const reqPayload = { ...data };
  return merchantFetch({
    url: 'store',
    method: 'post',
    data: reqPayload,
  });
}

export function updateStoreEntity(data) {
  const reqPayload = { ...data };
  return merchantFetch({
    url: 'store',
    method: 'put',
    data: reqPayload,
  });
}

export function deleteStoreEntity() {
  return merchantFetch({
    url: 'store',
    method: 'delete',
  });
}

export function saveProduct(payload, id) {
  if (id) {
    return merchantFetch({
      url: `store/products/${id}`,
      method: 'put',
      data: payload,
    });
  }

  return merchantFetch({
    url: 'store/products',
    method: 'post',
    data: payload,
  });
}

export function fetchProducts(data) {
  return merchantFetch({
    url: 'store/products',
    method: 'get',
    data,
  });
}

export function fetchProduct(id) {
  return merchantFetch({
    url: `store/products/${id}`,
    method: 'get',
  });
}

export function patchProduct(id, data) {
  return merchantFetch({
    url: `store/products/${id}`,
    method: 'patch',
    data,
  });
}
