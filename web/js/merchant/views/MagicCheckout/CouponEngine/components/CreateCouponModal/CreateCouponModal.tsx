import React, { Fragment } from 'react';
import { connect } from 'react-redux';
import { useNavigate } from 'react-router-dom';
import { bindActionCreators } from 'redux';
import { useSplitzService } from 'common/splitz';

// ui elements
import ModalHeader from 'common/ui/ModalHeader';
import {
  CategoryContainer,
  CouponName,
  CouponDescription,
  ChevronRightIcon,
  CategoryContent,
} from 'merchant/views/MagicCheckout/CouponEngine/components/CreateCouponModal/CreateCouponModalStyles';

// helpers and constants
import { getAvailableCouponTypes } from 'merchant/views/MagicCheckout/CouponEngine/constants';
import { closeModal } from 'merchant_common/reducers/modals';

const CreateCouponModal = ({ closeModal, isRcodEnabled }) => {
  const navigate = useNavigate();
  const { abExperiments } = useSplitzService();

  const navigateToCreateCouponForm = (type: string) => {
    navigate(`/magic/coupons/create/${type}`);

    // close the modal to select the coupon type
    closeModal();
  };
  /**
   * For MagicX , Free shipping coupon type is not available
   */
  const shouldShowFreeShippingCoupon =
    !isRcodEnabled && abExperiments?.magic_free_shipping_coupon?.variables?.result === 'on';

  const AVAILABLE_COUPON_TYPES = getAvailableCouponTypes(shouldShowFreeShippingCoupon);

  return (
    <Fragment>
      <ModalHeader title="Select a Coupon Type" extraClass="no-padding" onCloseClick={closeModal} />
      <div className="modal-body">
        {AVAILABLE_COUPON_TYPES.map(({ id, couponName, couponDesc, type }) => (
          <CategoryContainer
            key={id}
            className="display-flex justify-space-between align-center"
            onClick={() => {
              navigateToCreateCouponForm(type);
            }}
          >
            <CategoryContent>
              <CouponName>{couponName}</CouponName>
              <CouponDescription>{couponDesc}</CouponDescription>
            </CategoryContent>
            <ChevronRightIcon className="i i-chevron-right" />
          </CategoryContainer>
        ))}
      </div>
    </Fragment>
  );
};

const mapStateToProps = (state) => ({
  isRcodEnabled: state.magicCheckout.rcod,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      closeModal,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(CreateCouponModal);
