import React from 'react';
import { connect } from 'react-redux';
import { bindActionCreators, Dispatch } from 'redux';
import moment from 'moment';

// asset imports
import moreActions from 'assets/three-dots.svg';

// ui imports
import Input from 'common/new-ui/Input';
import Dropdown, { DropdownTrigger, DropdownContent } from 'common/ui/Dropdown';
import {
  StyledListItem,
  ActionText,
} from 'merchant/views/MagicCheckout/CouponEngine/components/ActionToolbar/ActionToolbarStyles';
import { Cta } from 'merchant/views/MagicCheckout/CouponEngine/components/MultiSelectHeader/MultiSelectHeaderStyles';

// api calls
import { deactivateCoupon, publishCoupon } from 'merchant/views/MagicCheckout/CouponEngine/api';

// helpers
import { openModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { openCreateCouponModal } from 'merchant/views/MagicCheckout/CouponEngine/helpers';

// constants and types
import { MultiSelectHeaderProps } from 'merchant/views/MagicCheckout/CouponEngine/components/MultiSelectHeader/types';
import { statusActionMap } from 'merchant/views/MagicCheckout/CouponEngine/components/MultiSelectHeader/constants';

const MultiSelectHeader: React.FC<MultiSelectHeaderProps> = ({
  onSelect,
  checked,
  disableMultiSelect,
  showAggregatedActions = false,
  openModal,
  checkedIds,
  setCheckedIds,
  showNotification,
  updateCouponsCb,
}) => {
  const handleCouponApiCall = async (coupon, actionName) => {
    try {
      if (actionName === 'in_active') {
        await deactivateCoupon(coupon);
      } else if (actionName === 'publish') {
        await publishCoupon(coupon);
      }
      updateCouponsCb(coupon, actionName);
      return true; // API call succeeded
    } catch (error) {
      console.error(`Error ${actionName}ing coupon ${coupon.id}:`, error);
      return false; // API call failed
    }
  };

  const handleBulkCouponAction = async (actionName, couponIds) => {
    // if coupon is active then we can do inactive! if we have inactive then can do active, if we have publish then we can do inactive
    const desiredStatus =
      actionName === 'in_active' ? 'active' : actionName === 'publish' ? 'created' : null;

    let couponsToProcess = couponIds.filter((coupon) => coupon.status === desiredStatus);

    if (actionName === 'publish') {
      // filtering out coupons which cant be publised
      couponsToProcess = couponsToProcess.filter((coupon) => {
        if (moment(coupon.active).isAfter(moment())) {
          if (!coupon.expiry || moment(coupon.expiry).isAfter(moment())) {
            return true;
          }
        }
        return false;
      });
    }
    const successCount = await Promise.all(
      couponsToProcess.map(async (coupon) => await handleCouponApiCall(coupon, actionName)),
    ).then((results) => results.filter(Boolean).length);

    const totalCount = couponIds.length;

    const displayActionName = {
      in_active: 'deactivated',
      publish: 'published',
    };

    if (successCount === totalCount) {
      showNotification({
        type: 'info',
        message: `${successCount} out of ${totalCount} selected coupons were ${displayActionName[actionName]} successfully.`,
      });
    } else if (successCount === 0) {
      if (couponsToProcess.length === 0) {
        showNotification({
          type: 'info',
          message:
            'You are unable to complete this action with the coupons you have chosen. Please ensure you select the appropriate coupons with the correct configurations and try once more.',
          closeTimeout: 12000,
        });
      } else {
        showNotification({
          type: 'error',
          message: `An error occurred while attempting to ${
            actionName === 'in_active' ? 'inactive' : actionName
          } the coupons. Please try again later.`,
        });
      }
    } else {
      showNotification({
        type: 'info',
        message: `${successCount} coupons out of ${totalCount} were successfully ${displayActionName[actionName]}, while others encountered errors.`,
        closeTimeout: 10000,
      });
    }

    setCheckedIds([]);
  };

  return (
    <div className="multi-select-header justify-space-between">
      <div className="display-flex">
        <Input.Check
          value="all"
          onChange={onSelect}
          checked={checked}
          disabled={disableMultiSelect}
          autoRender
          data-testid="select-all-coupons"
        />
        <p className="Input-desc">Select all</p>
      </div>
      <div>
        {showAggregatedActions ? (
          <Dropdown>
            <DropdownTrigger className="dropdown-toggle" data-testid="coupon-bulk-actions">
              <img src={moreActions} alt="more-actions" />
            </DropdownTrigger>
            <DropdownContent style={{ position: 'absolute', right: '200px' }}>
              <ul className="dropdown-menu" style={{ padding: 0 }}>
                {statusActionMap.map((action) => (
                  <StyledListItem
                    key={action.name}
                    onClick={() => {
                      handleBulkCouponAction(action.name, checkedIds);
                    }}
                  >
                    <ActionText>
                      <img src={action.icon} />
                      {action.displayValue}
                    </ActionText>
                  </StyledListItem>
                ))}
              </ul>
            </DropdownContent>
          </Dropdown>
        ) : (
          <Cta onClick={() => openCreateCouponModal(openModal)}>
            {' '}
            <i className="i i-plus" /> Create Coupon
          </Cta>
        )}
      </div>
    </div>
  );
};

const mapDispatchToProps = (dispatch: Dispatch) =>
  bindActionCreators(
    {
      openModal,
      showNotification,
    },
    dispatch,
  );
export default connect(null, mapDispatchToProps)(MultiSelectHeader);
