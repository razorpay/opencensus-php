import {
  activateCoupon,
  deleteCoupon,
  deactivateCoupon,
  publishCoupon,
} from 'merchant/views/MagicCheckout/CouponEngine/api';

export const modalTitleMap = {
  activate: 'Activate Coupon',
  inactivate: 'Deactivate Coupon',
  delete: 'Delete Coupon',
  publish: 'Publish Coupon',
};

export const successToastMessageMap = {
  activate: 'Coupon activated successfully',
  inactivate: 'Coupon deactivated successfully',
  delete: 'Coupon deleted successfully',
  publish: 'Coupon published successfully',
};

export const failureToastMessageMap = {
  activate: "Couldn't activate coupon. Please try again later",
  inactivate: "Couldn't deactivate coupon. Please try again later",
  delete: "Couldn't delete coupon. Please try again later",
  publish: "Couldn't publish coupon. Please try again later",
};

export const descriptionMap = {
  activate: 'Activating a coupon makes it available for customers on Checkout',
  inactivate: 'Deactivating a coupon makes it unavailable to customers on Checkout',
  delete: 'This coupon will be permanently deleted from your dashboard',
  publish:
    'Publishing a coupon makes it available to customers on Checkout when the coupon is active',
};

export const actionFunctionMap = {
  activate: activateCoupon,
  inactivate: deactivateCoupon,
  delete: deleteCoupon,
  publish: publishCoupon,
};

export const ctaTitleMap = {
  activate: 'Activate Coupon',
  inactivate: 'Deactivate Coupon',
  delete: 'Delete Coupon',
  publish: 'Publish Coupon',
};
