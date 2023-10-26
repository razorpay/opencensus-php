import lazy from 'merchant/routes/LazyLoader';

// constant imports
import {
  COUPON_TYPES,
  AVAILABLE_COUPON_TYPES,
} from 'merchant/views/MagicCheckout/CouponEngine/constants';

// ui element imports
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
const CreateCouponModal = lazy(() =>
  import(
    /* webpackChunkName: 'MagicCouponEngineCreateCouponModal' */ 'merchant/views/MagicCheckout/CouponEngine/components/CreateCouponModal/CreateCouponModal'
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
