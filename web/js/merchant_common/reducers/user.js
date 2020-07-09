const UPDATE_USER = 'UPDATE_USER';
const UPDATE_USER_ASYNC = 'UPDATE_USER_ASYNC';

export const updateContactMobile = (data, asyncCall) => ({
  type: UPDATE_USER_ASYNC,
  payload: asyncCall({
    url: `users/contact/update`,
    method: 'patch',
    data,
    mode: 'live',
  }),
});

export const updateUser = data => ({
  type: UPDATE_USER,
  data,
});
