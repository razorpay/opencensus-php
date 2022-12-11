import CardWrapper from 'merchant/views/MagicCheckout/common/components/CardWrapper';
import {
  GIFT_CARD_FEATURE,
  GIFT_CARD_SETTINGS,
  GC_FORM,
} from 'merchant/views/MagicCheckout/MagicSettings/constants';

const displayFeatureStatus = (status) => (status ? 'Enabled' : 'Disabled');
const getLastItemClass = (isLastItem) => (isLastItem ? ' setting-card-item-last' : '');

const GCCard = ({ settings, setCurrentView }) => {
  const { one_cc_gift_card } = settings;

  const switchToEdit = () => {
    setCurrentView(GC_FORM);
  };

  return (
    <CardWrapper switchToEdit={switchToEdit} cardTitle="Gift Card" extraClass="gift-card">
      <div className={`flex setting-card-item gap--12${getLastItemClass(!one_cc_gift_card)}`}>
        <CardWrapper.Item
          label={GIFT_CARD_FEATURE.label}
          value={displayFeatureStatus(one_cc_gift_card)}
        />
      </div>
      {one_cc_gift_card &&
        GIFT_CARD_SETTINGS.map(({ label, key }, index) => (
          <div
            key={key}
            className={`flex setting-card-item gap--12${getLastItemClass(
              index === GIFT_CARD_SETTINGS.length - 1,
            )}`}
          >
            <CardWrapper.Item label={label} value={displayFeatureStatus(settings[key])} />
          </div>
        ))}
    </CardWrapper>
  );
};

export default GCCard;
