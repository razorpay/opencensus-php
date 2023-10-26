import React from 'react';
import { bindActionCreators, Dispatch } from 'redux';
import { connect } from 'react-redux';

// API and Redux helpers
import { saveCustomerDetailsList } from 'merchant/views/MagicCheckout/CouponEngine/api';
import { closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

// UI components
import { ModalCtaWrapper } from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/CreateCouponFormStyles';

// Define props types
interface ModalActionsProps {
  closeModal: () => void;
  showNotification: (notification: { type: string; message: string }) => void;
  eligibleCustomersDetails: {
    id: string;
  };
  handleSelectedData: (details: any) => void;
}

// ModalActions component
const ModalActions: React.FC<ModalActionsProps> = ({
  closeModal,
  showNotification,
  eligibleCustomersDetails,
  handleSelectedData,
}) => {
  const handleModalConfirm = async () => {
    try {
      const { data } = await saveCustomerDetailsList(eligibleCustomersDetails);
      handleSelectedData({ ...eligibleCustomersDetails, ...data });
      closeModal();
    } catch (error) {
      showNotification({
        type: 'error',
        message: 'Something went wrong. Please try again.',
      });
    }
  };

  return (
    <ModalCtaWrapper>
      <div>{eligibleCustomersDetails.id ? 1 : 0} file uploaded</div>
      <div>
        <button className="secondary-cta" onClick={closeModal}>
          Cancel
        </button>
        <button onClick={handleModalConfirm} className="primary-cta">
          Confirm
        </button>
      </div>
    </ModalCtaWrapper>
  );
};

const mapDispatchToProps = (dispatch: Dispatch) =>
  bindActionCreators(
    {
      closeModal,
      showNotification,
    },
    dispatch,
  );

export default connect(null, mapDispatchToProps)(ModalActions);
