import ajax from 'merchant/utils/ajax';

export default (data = {}) =>
  ajax({
    url: '/addfunds',
    method: 'post',
    data,
  });
