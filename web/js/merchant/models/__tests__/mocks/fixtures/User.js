import User from 'merchant/models/User';

const getDefaultUserObj = (props = {}) =>
  new User({
    name: 'test-user',
    current: 'test-id',
    ...props,
  });

export { getDefaultUserObj };
