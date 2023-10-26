import React from 'react';

// ui imports
import Input from 'common/new-ui/Input';
import ActionToolbar from 'merchant/views/MagicCheckout/CouponEngine/components/ActionToolbar/ActionToolbar';
import {
  CouponName,
  CouponDescription,
} from 'merchant/views/MagicCheckout/CouponEngine/styles/DataTableElements';
import { CouponStatus } from 'merchant/views/MagicCheckout/CouponEngine/styles/CouponStatus';
import Popover, { PopoverBody } from 'common/ui/Popover';

// helpers
import { getLabelFromName } from 'merchant/views/MagicCheckout/CouponEngine/helpers';
import { truncatedString } from 'common/utils/rzp-utils';

// Component to display coupon code along with a checkbox
export const couponCode = (onSelectId, checkedIds) => ({
  title: 'Coupon',
  value: (coupon) => (
    <div className="orderId-container">
      <Input.Check
        autoRender
        id={coupon.id}
        onChange={(e) => onSelectId(e, coupon)}
        value={coupon.id}
        checked={checkedIds.some((item) => item.id === coupon.id)}
        data-testid={`coupon-${coupon.id}`}
      />
      <CouponName> {truncatedString(coupon.code, 30)} </CouponName>
      {coupon.code.length > 30 && (
        <Popover>
          <PopoverBody>
            <CouponName> {coupon.code} </CouponName>
          </PopoverBody>
        </Popover>
      )}
    </div>
  ),
});

// Component to display coupon code without a checkbox
export const couponCodeWithoutCheckBox = {
  title: 'Coupon',
  value: (coupon) => {
    return (
      <>
        <CouponName> {truncatedString(coupon.code, 30)} </CouponName>
        {coupon.code.length > 30 && (
          <Popover>
            <PopoverBody>
              <CouponName> {coupon.code} </CouponName>
            </PopoverBody>
          </Popover>
        )}
      </>
    );
  },
};

// Component to display coupon type
export const couponType = {
  title: 'Type',
  value: (coupon: any) => (coupon.type ? getLabelFromName(coupon.type) : '-'),
};

// Component to display whether the coupon is for display
export const checkoutDisplayStatus = {
  title: 'Display',
  value: (coupon: any) => {
    const status = coupon.status ?? '-';
    return <span> {status === '-' ? '-' : coupon.display ? 'Yes' : 'No'}</span>;
  },
};

// Component to display coupon usage count
export const couponUseageCount = {
  title: 'Use',
  value: (coupon: any) => coupon.usage_count ?? '-',
};

// Component to display coupon status with styling
export const couponStatus = {
  title: 'Status',
  value: (coupon: any) => {
    let displayStatus = coupon.status ?? '-';
    if (coupon.status === 'in_active') {
      displayStatus = 'inactive';
    }
    return <CouponStatus variant={coupon.status}>{displayStatus}</CouponStatus>;
  },
};

// Component to display actions toolbar for coupons
export const actions = (updateCouponsCb?) => ({
  title: 'Action',
  columnClass: 'text-right',
  value: (coupon: any) => {
    return (
      <ActionToolbar
        couponStatus={coupon.status}
        coupon={coupon}
        updateCouponsCb={updateCouponsCb}
      />
    );
  },
});

// Component to display truncated coupon description
export const couponDescription = {
  title: 'Description',
  value: (coupon: any) =>
    coupon?.description ? (
      <>
        <CouponDescription>{truncatedString(coupon.description, 22)}</CouponDescription>
        {coupon.description.length > 22 && (
          <Popover>
            <PopoverBody className="align-center">
              <CouponDescription>{coupon.description}</CouponDescription>
            </PopoverBody>
          </Popover>
        )}
      </>
    ) : (
      '-'
    ),
};

// Component to display whether auto apply is enabled for the coupon
export const autoApplyStatus = {
  title: 'AutoApply',
  value: (coupon: any) => {
    const status = coupon.auto_apply ?? '-';
    return <span> {status === '-' ? '-' : coupon.display ? 'Yes' : 'No'}</span>;
  },
};
