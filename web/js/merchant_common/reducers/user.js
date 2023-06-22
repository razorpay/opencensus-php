import { merchantFetch } from 'merchant/utils/ajax';

const UPDATE_USER = 'UPDATE_USER';
const UPDATE_USER_ASYNC = 'UPDATE_USER_ASYNC';

export const updateContactMobile = (data) => ({
  type: UPDATE_USER_ASYNC,
  payload: merchantFetch({
    url: `users/contact/sendotp`,
    method: 'post',
    data,
    mode: 'live',
  }),
});

export const updateUser = (data) => ({
  type: UPDATE_USER,
  data,
});

export const updateUserName = (data) => ({
  type: UPDATE_USER_ASYNC,
  payload: merchantFetch({
    url: 'users/update_name',
    method: 'post',
    data,
  }),
});
