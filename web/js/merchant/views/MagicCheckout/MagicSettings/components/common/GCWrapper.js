import { useEffect } from 'react';
import GCCard from 'merchant/views/MagicCheckout/MagicSettings/containers/common/GCCard';
import GCForm from 'merchant/views/MagicCheckout/MagicSettings/containers/common/GCForm';
import {
  FETCH_STATUS,
  GC_FORM,
  GC_CARD,
} from 'merchant/views/MagicCheckout/MagicSettings/constants';

const GCWrapper = ({
  giftCard,
  giftCardSettings,
  settings,
  showFormView,
  setCurrentView,
  setGiftCard,
  setGiftCardSettings,
}) => {
  const {
    nestedTabsStatus,
    one_cc_gift_card,
    one_cc_multiple_gift_card,
    one_cc_gift_card_restrict_coupon,
    one_cc_buy_gift_card,
    one_cc_gift_card_cod_restrict,
  } = settings;

  useEffect(() => {
    if (nestedTabsStatus !== FETCH_STATUS.LOADING) {
      setCurrentView(GC_CARD);
    } else {
      setCurrentView(GC_FORM);
    }
  }, [
    one_cc_gift_card,
    one_cc_multiple_gift_card,
    one_cc_gift_card_restrict_coupon,
    one_cc_buy_gift_card,
    one_cc_gift_card_cod_restrict,
    nestedTabsStatus,
  ]);

  return (
    <>
      {showFormView ? (
        <GCForm
          settings={settings}
          giftCard={giftCard}
          giftCardSettings={giftCardSettings}
          setGiftCard={setGiftCard}
          setGiftCardSettings={setGiftCardSettings}
        />
      ) : (
        <GCCard settings={settings} setCurrentView={setCurrentView} />
      )}
    </>
  );
};

export default GCWrapper;
