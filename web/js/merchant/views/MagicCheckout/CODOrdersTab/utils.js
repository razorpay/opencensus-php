import moment from 'moment';
import ConfirmationModal from 'merchant/views/MagicCheckout/common/components/ConfirmationModal';
import {
  REVIEW_CONFIRMATION_TEXTS,
  ALERT_MESSAGES,
  RISK_TIER,
} from 'merchant/views/MagicCheckout/CODOrdersTab/constants';

export const sortDateUtil = (itemsArray, sortType) =>
  sortType === 'ascend'
    ? [...itemsArray].sort((a, b) => new Date(a.created_at) - new Date(b.created_at))
    : [...itemsArray].sort((a, b) => new Date(b.created_at) - new Date(a.created_at));

export const sortRiskTierUtil = (itemsArray = [], sortType) => {
  const high = itemsArray.filter((a) => a.risk_tier === RISK_TIER.high);
  const low = itemsArray.filter((a) => a.risk_tier === RISK_TIER.low);
  const medium = itemsArray.filter((a) => a.risk_tier === RISK_TIER.medium);

  return sortType === 'ascend' ? [...low, ...medium, ...high] : [...high, ...medium, ...low];
};

export const showResultNotification = (args) => {
  const { promise, showNotification, closeModal, isChecked = new Set(), reviewType } = args;
  promise
    .then((res) => {
      const isSuccess = res.data
        .filter((item) => !item.hasOwnProperty('error'))
        .map((item) => item.id);
      if (res.data?.length === 1) {
        if (res.data[0].hasOwnProperty('error')) {
          showNotification({
            type: 'neutral',
            message: `${ALERT_MESSAGES.error[res.data[0].error.data.review_status]} ${
              res.data[0].error.data.reviewed_by
            }`,
            closeTimeout: 10000,
            className: 'magic-notification',
          });
        } else {
          showNotification({
            type: 'success',
            message: `Order ${ALERT_MESSAGES.success[reviewType]}`,
            closeTimeout: 10000,
            className: 'magic-notification',
          });
        }
      } else if (isSuccess.length === isChecked.size) {
        showNotification({
          type: 'success',
          message: `Orders ${ALERT_MESSAGES.success[reviewType]}`,
          closeTimeout: 10000,
          className: 'magic-notification',
        });
      } else {
        showNotification({
          type: 'neutral',
          message:
            'The selected list contains some already reviewed orders. Please refresh the page to update status.',
          closeTimeout: 10000,
          className: 'magic-notification',
        });
      }
      closeModal();
    })
    .catch(() => {
      showNotification({
        type: 'error',
        message: 'something went wrong, please try again',
        className: 'magic-notification',
      });
    });
};

export const confirmReview = (args) => {
  const { orderId, reviewType, isChecked, openModal, onConfirm } = args;

  const { heading, desc, affirmativeLabel, abortLabel } = REVIEW_CONFIRMATION_TEXTS[reviewType];
  const header = orderId
    ? `${reviewType} ${orderId}?`
    : heading[isChecked.size === 1 ? 'singular' : 'plural'];
  const confirmLabel =
    orderId || isChecked.size === 1 ? affirmativeLabel.singular : affirmativeLabel.plural;

  openModal({
    size: 'small',
    className: `${reviewType ?? 'appove'}-order-confirmation`,
    component: (
      <ConfirmationModal
        header={header}
        desc={desc}
        affirmativeLabel={confirmLabel}
        abortLabel={abortLabel}
        onAffirm={() => onConfirm(orderId, reviewType)}
      />
    ),
  });
};

export const getPresetsValue = (presets) => {
  const now = moment();
  const newPresets = presets.map((preset) => {
    const text = preset[0];
    const rest = preset.slice(1);
    const timeStampDiff =
      now.unix() -
      now
        .clone()
        .add(...rest)
        .unix();

    const result = { name: text, value: timeStampDiff };

    return result;
  });

  return newPresets;
};
