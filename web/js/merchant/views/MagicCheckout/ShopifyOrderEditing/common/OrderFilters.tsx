import React, { useRef } from 'react';
import ListFilter from 'merchant/components/ListFilter';

interface OrderFiltersProps {
  formName?: string;
  onSubmitHandler: (search: string) => void;
  resetHandler: () => void;
}

const OrderFilters: React.FC<OrderFiltersProps> = ({
  formName = 'filters',
  onSubmitHandler,
  resetHandler,
}) => {
  const inputRef = useRef<HTMLInputElement>(null);

  const handleReset = () => {
    if (inputRef.current) {
      inputRef.current.value = '';
    }
    resetHandler();
  };

  return (
    <ListFilter
      form={`${formName}-form`}
      onSubmit={() => onSubmitHandler(inputRef.current?.value || '')}
      resetHandler={handleReset}
    >
      <div className="form-group list-filter-item">
        <label htmlFor="shopify-order-id">Shopify Order ID</label>
        <input
          id="shopify-order-id"
          type="number"
          pattern="[0-9]*"
          name="id"
          className="form-control input-sm"
          ref={inputRef}
        />
      </div>
    </ListFilter>
  );
};

export default OrderFilters;
