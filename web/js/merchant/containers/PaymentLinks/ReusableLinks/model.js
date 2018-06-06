import { merchantFetch } from 'rzp/utils/ajax';

export const createReusableLink = data => {
  const curDirtyForm = this.state.dirty[this.state.activeTab];

  const reqPayload = {
    ...curDirtyForm,
    type: 'link',
    currency: 'INR', // TODO: Get is dynamically
  };

  reqPayload.amount *= 100;
  reqPayload.expire_by = Math.round(reqPayload.expire_by / 1000);

  return merchantFetch({
    url: 'payment_links',
    method: 'post',
    data: reqPayload,
  });
};

export const fetchReusableLinksEntity = id =>
  merchantFetch(`payment_links/${id}`);

export const fetchReusableLinksList = data =>
  merchantFetch({
    url: 'payment_links',
    data,
  });
