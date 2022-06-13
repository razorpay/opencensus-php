import Button from 'common/new-ui/Button/index';
import { ATTRIBUTE_TYPE } from 'merchant/views/MagicCheckout/MagicIntelligence/constants';

export const type = { title: 'Type', value: (item) => ATTRIBUTE_TYPE[item.attribute_type] || '-' };

export const value = { title: 'Value', value: (item) => item.attribute_value || '-' };

export const addedOn = {
  title: 'Added On',
  value: (item) => {
    const date = new Date(item.created_at * 1000);
    const fullDate = `${date.getDate()}-${date.getMonth() + 1}-${date.getFullYear()}`;
    return fullDate;
  },
};

export const addedBy = { title: 'Added By', value: (item) => item.created_by || '-' };

export const actions = ({ onDeleteClick }) => ({
  title: 'Action',
  value: (item) => {
    return (
      <Button.Transparent className="font-normal" type="button" onClick={onDeleteClick(item.id)}>
        <i className="i i-delete" />
      </Button.Transparent>
    );
  },
});
