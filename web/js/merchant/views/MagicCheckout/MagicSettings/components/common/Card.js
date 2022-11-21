import CardWrapper from 'merchant/views/MagicCheckout/MagicSettings/components/common/CardWrapper';

const displayFeatureStatus = (status) => (status ? 'Enabled' : 'Disabled');
const getLastItemClass = (isLastItem) => (isLastItem ? 'setting-card-item-last' : '');

const Card = ({ settings, switchToEdit, cardTitle, cardItems, extraClass }) => {
  return (
    <CardWrapper switchToEdit={switchToEdit} cardTitle={cardTitle} extraClass={extraClass}>
      {cardItems.map(({ label, key }, index) => (
        <div
          key={key}
          className={`flex setting-card-item gap--12 ${getLastItemClass(
            index === cardItems.length - 1,
          )}`}
        >
          <CardWrapper.Item label={label} value={displayFeatureStatus(settings[key])} />
        </div>
      ))}
    </CardWrapper>
  );
};

export default Card;
