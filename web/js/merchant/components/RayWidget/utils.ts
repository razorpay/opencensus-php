import { User } from 'common/typings';

export const getRayUser = ({ role, user, merchant, activation_status }: User) => ({
  id: user?.id,
  name: user?.name,
  email: user?.email,
  contact_mobile: user?.contact_mobile,
  role: role,
  activation_status,
  merchant: {
    id: merchant?.id,
    name: merchant?.name,
    display_name: merchant?.display_name,
    email: merchant?.email,
  },
});
