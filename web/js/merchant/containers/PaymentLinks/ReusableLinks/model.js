import {
  getActionName,
  makeCollectionReducer,
  makeActionCollectionReducer,
} from 'rzp/modules/collection';
import { merchantFetch } from 'rzp/utils/ajax';

export const createReusableLink = data => {
  return merchantFetch({
    url: 'payment_links',
    method: 'post',
    data,
  })
    .then(response => {
      return response;
    })
    .catch(err => {
      this.props.showNotification({
        type: 'error',
        message: err.errors,
      });

      throw err;
    });
};

export const fetchReusableLinksEntity = id => {
  return merchantFetch({
    url: `payment_links/${id}`,
  })
    .then(response => {
      return response;
    })
    .catch(err => {
      this.props.showNotification({
        type: 'error',
        message: err.errors,
      });

      throw err;
    });
};

export function fetchReusableLinksList(params) {
  return merchantFetch({
    url: 'payment_links',
    data: params,
  });
}
