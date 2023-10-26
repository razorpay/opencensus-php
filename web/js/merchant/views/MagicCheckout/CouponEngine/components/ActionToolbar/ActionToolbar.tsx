import React from 'react';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';
import { bindActionCreators, Dispatch } from 'redux';
import lazy from 'merchant/routes/LazyLoader';

// ui imports
import Dropdown, { DropdownTrigger, DropdownContent } from 'common/ui/Dropdown';
import {
  StyledListItem,
  ActionText,
} from 'merchant/views/MagicCheckout/CouponEngine/components/ActionToolbar/ActionToolbarStyles';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';

// helpers/types imports
import { openModal } from 'merchant_common/reducers/modals';
import { ActionToolbarProps } from 'merchant/views/MagicCheckout/CouponEngine/components/ActionToolbar/types';

// assets imports
import moreActions from 'assets/three-dots.svg';

// constants
import { statusActionMap } from 'merchant/views/MagicCheckout/CouponEngine/components/ActionToolbar/constants';

const ModifyCouponStatus = lazy(
  () =>
    import(
      /* webpackChunkName: 'MagicCouponEngineModifyCouponStatusModal' */ 'merchant/views/MagicCheckout/CouponEngine/components/ModifyCouponStatus/ModifyCouponStatus'
    ),
);

const ActionToolbar: React.FC<ActionToolbarProps> = ({ openModal, coupon, updateCouponsCb }) => {
  const { status, type: couponType, code } = coupon;

  return (
    <div className="d-inline-block">
      <Dropdown>
        <DropdownTrigger className="dropdown-toggle">
          <img src={moreActions} alt="more-actions" />
        </DropdownTrigger>
        <DropdownContent style={{ position: 'absolute', right: '200px' }}>
          <ul className="dropdown-menu" style={{ padding: 0 }}>
            {(statusActionMap[status] || []).map((action) => (
              <div key={action.name}>
                {action.name === 'edit' || action.name === 'duplicate' ? (
                  <NavLink to={`coupons/${action.name}/${couponType}/${encodeURIComponent(code)}`}>
                    <StyledListItem key={action.name}>
                      <ActionText>
                        <img src={action.icon} />
                        {action.displayValue}
                      </ActionText>
                    </StyledListItem>
                  </NavLink>
                ) : (
                  <StyledListItem
                    key={action.name}
                    onClick={() => {
                      if (['activate', 'inactivate', 'delete', 'publish'].includes(action.name)) {
                        openModal({
                          size: 'small',
                          component: (
                            <SuspenseWithLoader type="center">
                              <ModifyCouponStatus
                                status={action.name}
                                coupon={coupon}
                                updateCouponsCb={updateCouponsCb}
                              />
                            </SuspenseWithLoader>
                          ),
                        });
                      }
                    }}
                  >
                    <ActionText>
                      <img src={action.icon} />
                      {action.displayValue}
                    </ActionText>
                  </StyledListItem>
                )}
              </div>
            ))}
          </ul>
        </DropdownContent>
      </Dropdown>
    </div>
  );
};

const mapDispatchToProps = (dispatch: Dispatch) =>
  bindActionCreators(
    {
      openModal,
    },
    dispatch,
  );

export default connect(null, mapDispatchToProps)(ActionToolbar);
