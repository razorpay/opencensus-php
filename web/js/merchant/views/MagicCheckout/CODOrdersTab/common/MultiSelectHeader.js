import Input from 'common/new-ui/Input';
import ActionToolbar from 'merchant/views/MagicCheckout/CODOrdersTab/common/ActionToolbar';

const MultiSelectHeader = (props) => {
  const { onSelect, checked, isChecked, onReview, disableMultiSelect, hideHold } = props;
  return (
    <div className="multi-select-header">
      <Input.Check
        value={'all'}
        onChange={onSelect}
        checked={checked}
        disabled={disableMultiSelect}
        autoRender
      />
      <p className="Input-desc">Select All</p>
      {(checked || isChecked.size !== 0) && (
        <ActionToolbar
          onReview={onReview}
          isChecked={isChecked}
          extraClass=" multi-action-toolbar"
          disableActions={false}
          hideHold={hideHold}
        />
      )}
    </div>
  );
};

export default MultiSelectHeader;
