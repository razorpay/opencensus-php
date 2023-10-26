import React, { Fragment } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators, Dispatch } from 'redux';

// ui components
import { Cta } from 'merchant/views/MagicCheckout/CouponEngine/components/MultiSelectHeader/MultiSelectHeaderStyles';
import { EmptyCouponDescription } from 'merchant/views/MagicCheckout/CouponEngine/styles/DataTableElements';
import EmptyList from 'merchant/components/EmptyList';

// helpers
import { openCreateCouponModal } from 'merchant/views/MagicCheckout/CouponEngine/helpers';
import { openModal } from 'merchant_common/reducers/modals';

interface EmptyComponentProps {
  openModal: (options: { size: string; className?: string; component: JSX.Element }) => void;
}

const EmptyComponent: React.FC<EmptyComponentProps> = ({ openModal }) => {
  return (
    <EmptyList
      description={
        <Fragment>
          <EmptyCouponDescription>You haven’t created any coupons</EmptyCouponDescription>

          <Cta onClick={() => openCreateCouponModal(openModal)}>
            <i className="i i-plus" /> Create Coupon
          </Cta>
        </Fragment>
      }
    />
  );
};

const mapDispatchToProps = (dispatch: Dispatch) =>
  bindActionCreators(
    {
      openModal,
    },
    dispatch,
  );
export default connect(null, mapDispatchToProps)(EmptyComponent);
