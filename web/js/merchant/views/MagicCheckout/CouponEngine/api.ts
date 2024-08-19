import { merchantFetch } from 'merchant/utils/ajax';

// Coupon Actions
export const activateCoupon = (data: any): any => {
  return merchantFetch({
    url: '1cc/dashboard/ce/coupons',
    method: 'put',
    data: {
      ...data,
      status: 'active',
    },
  });
};

export const deactivateCoupon = (data: any): any => {
  return merchantFetch({
    url: '1cc/dashboard/ce/coupons',
    method: 'put',
    data: {
      ...data,
      status: 'in_active',
    },
  });
};

export const publishCoupon = (data: any): any => {
  return merchantFetch({
    url: '1cc/dashboard/ce/coupons',
    method: 'put',
    data: {
      ...data,
      status: 'published',
    },
  });
};

export const deleteCoupon = (data: any): any => {
  return merchantFetch({
    url: `1cc/dashboard/ce/coupons?id=${data.id}&app_type=${data.app_type}`,
    method: 'delete',
  });
};

export const getCoupon = (code?: string): any => {
  return merchantFetch({
    url: `1cc/dashboard/ce/coupons?code=${code}&skip=0&count=1&desc_order=true`,
    method: 'get',
  });
};

export const listCoupons = (payload: any = {}): any => {
  const {
    type = '',
    code = '',
    status = '',
    sort_by = 'date-desc',
    skip = 0,
    count = 10,
    display,
    source = '',
  } = payload;

  const data = {
    desc_order: sort_by === 'date-desc' ? 'true' : 'false',
    count,
    skip,
    coupon_display: display === 'all' ? '' : display === 'yes' ? 'true' : 'false',
    status: status === 'all' ? '' : status,
    type: type === 'all' ? '' : type,
    source: source === 'all' ? '' : source,
    code_search_term: code,
  };
  return merchantFetch({
    url: `1cc/dashboard/ce/coupons`,
    method: 'get',
    data,
  });
};

export const createCoupon = (data: any): any => {
  return merchantFetch({
    url: '1cc/dashboard/ce/coupons',
    method: 'put',
    data,
  });
};

// Products and Collections
export const getCollections = (limit = 15, cursor = '', searchTerm = ''): any => {
  return merchantFetch({
    url: `1cc/magic/platform/products/collections/search?skip=0&count=${limit}&cursor=${cursor}&query=${searchTerm}`,
    method: 'get',
  });
};

export const getProducts = (limit: number, offset: number, search_term: string): any => {
  return merchantFetch({
    url: `1cc/magic/platform/products/search?skip=${offset}&count=${limit}&search_term=${search_term}`,
    method: 'get',
  });
};

// Shopify Coupon Sync
export const syncShopifyCoupons = (data: any): any => {
  return merchantFetch({
    url: '1cc/dashboard/ce/coupons/sync',
    method: 'post',
    data,
  });
};

export const getSyncShopifyCouponsStatus = (): any => {
  return merchantFetch({
    url: '1cc/dashboard/ce/coupons/sync',
    method: 'get',
  });
};

// Customer Details - Integration with UFH
export const getCustomerDetailsList = (type: string): any => {
  return merchantFetch({
    url: `1cc/dashboard/ce/segments?type=${type}`,
    method: 'get',
  });
};

export const saveCustomerDetailsList = (data: any): any => {
  return merchantFetch({
    url: '1cc/dashboard/ce/segments',
    method: 'post',
    data,
  });
};

export const uploadCustomerDetailsToUfh = (
  file: File,
  progressTracker: (progress: ProgressEvent) => void,
): any => {
  const formData = new FormData();
  formData.append('file', file);
  formData.append('type', 'onecc_merchant_segments_data');
  formData.append('name', `${window.rzp_user?.user?.id}_${Date.now()}`);

  const CUSTOM_HEADERS = {
    'X-Dashboard-User-Id': window.rzp_user?.user?.id,
  };

  return merchantFetch({
    url: 'ufh/files/upload',
    method: 'post',
    data: formData,
    headers: CUSTOM_HEADERS,
    onUploadProgress: progressTracker,
  });
};
