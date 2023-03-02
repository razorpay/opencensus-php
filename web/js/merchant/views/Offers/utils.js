import rolesList from 'merchant/helpers/permissions/roles-list';

const { ADMIN, OWNER, SELLERAPP } = rolesList;
export const isOfferIdClickable = (user) => [ADMIN, OWNER, SELLERAPP].includes(user.role);
