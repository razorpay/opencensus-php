import lazy from 'merchant/routes/LazyLoader';
import isEmpty from 'lodash/isEmpty';

//helper imports
import {
  createCartDiscountPayload,
  createProductDiscountPayload,
  createBuyXGetYPayload,
  createBulkDiscountPayload,
} from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/helpers/createCouponPayloads';

// constant imports
import {
  COUPON_TYPES,
  AVAILABLE_COUPON_TYPES,
  CREATE_COUPON_CONFIRMATION_MODAL_CONTENT,
} from 'merchant/views/MagicCheckout/CouponEngine/constants';

// ui element imports
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
const CreateCouponModal = lazy(() =>
  import(
    /* webpackChunkName: 'MagicCouponEngineCreateCouponModal' */ 'merchant/views/MagicCheckout/CouponEngine/components/CreateCouponModal/CreateCouponModal'
  ),
);
const ConfirmationModal = lazy(() =>
  import(
    /* webpackChunkName: 'MagicCouponEngineConfirmationModal' */ 'merchant/views/MagicCheckout/common/components/ConfirmationModal'
  ),
);

export function getLabelFromName(name) {
  const couponType = COUPON_TYPES.find((type) => type.name === name);
  return couponType ? couponType.label : 'Invalid Coupon Type';
}

export function getDisplayCouponType(type) {
  const couponType = AVAILABLE_COUPON_TYPES.find((coupon) => coupon.type === type);
  return couponType ? couponType.couponName : 'Invalid Coupon Type';
}

export const openCreateCouponModal = (openModal) => {
  openModal({
    size: 'large',
    className: `create-coupon-modal`,
    component: (
      <SuspenseWithLoader type="center">
        {' '}
        <CreateCouponModal />{' '}
      </SuspenseWithLoader>
    ),
  });
};

export function isCreateCouponValid(input) {
  if (isEmpty(input)) {
    return true;
  }
  if (typeof input === 'object') {
    for (const item of Object.values(input)) {
      // if item is not undefined and is a primitive, return false
      // otherwise dig deeper
      if ((item !== undefined && typeof item !== 'object') || !isCreateCouponValid(item)) {
        return false;
      }
    }
    return true;
  }
  return isEmpty(input);
}

export const openCreateCouponConfirmationModal = (openModal, closeModal) => {
  return new Promise((resolve) => {
    openModal({
      size: 'small',
      className: 'magicToggleConfirmationModal',
      component: (
        <SuspenseWithLoader type="center">
          <ConfirmationModal
            header={CREATE_COUPON_CONFIRMATION_MODAL_CONTENT.header}
            desc={CREATE_COUPON_CONFIRMATION_MODAL_CONTENT.desc}
            affirmativeLabel={CREATE_COUPON_CONFIRMATION_MODAL_CONTENT.affirmativeLabel}
            abortLabel={CREATE_COUPON_CONFIRMATION_MODAL_CONTENT.abortLabel}
            onAffirm={() => {
              resolve(true);
              closeModal();
            }}
            onAbort={() => {
              resolve(false);
              closeModal();
            }}
          />
        </SuspenseWithLoader>
      ),
    });
  });
};

export const createApiData = (couponName, data) => {
  switch (couponName) {
    case 'amount_off_order':
      return createCartDiscountPayload(data);
    case 'amount_off_products':
      return createProductDiscountPayload(data);
    case 'buyx_gety':
      return createBuyXGetYPayload(data);
    case 'bulk_order':
      return createBulkDiscountPayload(data);
    default:
      return null;
  }
};
