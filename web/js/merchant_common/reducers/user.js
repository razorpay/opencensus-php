const UPDATE_SESSION = 'UPDATE_SESSION';

export const updateContactMobile = (data, asyncCall) => ({
  type: UPDATE_SESSION,
  payload: asyncCall({
    url: `users/contact/update`,
    method: 'patch',
    data,
    mode: 'live',
  }),
});
