import ConfirmationModal from 'merchant/views/MagicCheckout/common/components/ConfirmationModal';
import { PL_NOTIFICATION_MSG } from 'merchant/views/MagicCheckout/CODToPrepaid/CODToPrepaidLinks/constants';

export const onConfirmReview = ({
  paymentLinkId,
  reviewType,
  reviewPrepayCODOrders,
  showNotification,
  closeModal,
}) => {
  reviewPrepayCODOrders({
    action: reviewType,
    id: paymentLinkId,
  })
    .then(() => {
      showNotification({
        type: 'success',
        message: PL_NOTIFICATION_MSG.expireSuccess,
        className: 'magic-cod-prepaid-notification',
      });
    })
    .catch(() => {
      showNotification({
        type: 'error',
        message: PL_NOTIFICATION_MSG.expireError,
        className: 'magic-cod-prepaid-notification',
      });
    })
    .finally(() => {
      closeModal();
    });
};

export const openConfirmationModal = ({
  openModal,
  modalInfo,
  paymentLinkId,
  reviewType,
  reviewPrepayCODOrders,
  showNotification,
  closeModal,
}) => {
  openModal({
    size: 'small',
    className: `expire-link-confirmation`,
    component: (
      <ConfirmationModal
        header={modalInfo.heading}
        desc={modalInfo.description}
        affirmativeLabel={modalInfo.affirmativeLabel}
        abortLabel={modalInfo.abortLabel}
        onAffirm={() =>
          onConfirmReview({
            paymentLinkId,
            reviewType,
            reviewPrepayCODOrders,
            showNotification,
            closeModal,
          })
        }
      />
    ),
  });
};
