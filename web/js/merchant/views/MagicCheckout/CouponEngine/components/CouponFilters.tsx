import React, { useState, useEffect, ChangeEvent } from 'react';

// ui imports
import Input from 'common/new-ui/Input';
import ListFilter from 'merchant/components/ListFilter';

// constants imports
import {
  COUPON_TYPES,
  COUPON_STATUS,
  SORT_BY,
  COUPON_DISPLAY,
  COUPON_SOURCES,
  COUNT,
} from 'merchant/views/MagicCheckout/CouponEngine/constants';

// Define the shape of the form data
interface FormData {
  type: string;
  code: string;
  status: string;
  sort_by: string;
  skip: number;
  count: number;
  display: string;
  source: string;
}

// Props for the CouponFilters component
interface CouponFiltersProps {
  formName?: string;
  onSubmitHandler: (formData: FormData) => void;
  resetHandler: () => void;
  tabName?: string;
}

const initialFiltersState: FormData = {
  type: 'all',
  code: '',
  status: 'all',
  sort_by: 'date-desc',
  skip: 0,
  count: 10,
  display: 'all',
  source: 'all',
};

const CouponFilters: React.FC<CouponFiltersProps> = ({
  formName = 'filters',
  onSubmitHandler,
  resetHandler,
  tabName = 'all',
}) => {
  const [formData, setFormData] = useState<FormData>(initialFiltersState);

  useEffect(() => {
    if (tabName === 'active' || tabName === 'expired') {
      setFormData((prevState) => ({ ...prevState, status: tabName }));
    }
  }, [tabName]);

  // Handle input and select changes
  const setField = (e: ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
    const { name, value } = e.target;
    setFormData((prevState) => ({ ...prevState, [name]: value }));
  };

  // Handle form submission
  const handleFormSubmit = () => {
    onSubmitHandler(formData);
  };

  // Handle form reset
  const handleResetHandler = () => {
    setFormData(initialFiltersState);
    resetHandler();
  };

  return (
    <ListFilter
      form={`${formName}-form`}
      onSubmit={handleFormSubmit}
      resetHandler={handleResetHandler}
    >
      {/* Input for Coupon Code */}
      <div className="form-group list-filter-item" style={{ flexBasis: '295px' }}>
        <label htmlFor="couponName">Coupon Code</label>
        <input
          name="code"
          type="text"
          className="form-control input-sm"
          value={formData.code}
          onChange={setField}
        />
      </div>

      {/* Select for Coupon Type */}
      <div className="form-group list-filter-item">
        <label htmlFor="couponType">Type</label>
        <Input.Select
          name="type"
          options={COUPON_TYPES}
          value={formData.type}
          onChange={setField}
        />
      </div>

      {/* Select for Coupon Status */}
      <div className="form-group list-filter-item">
        <label htmlFor="couponStatus">Status</label>
        <Input.Select
          name="status"
          options={COUPON_STATUS}
          value={formData.status}
          onChange={setField}
          disabled={tabName === 'expired' || tabName === 'active'}
        />
      </div>

      {/* Select for Sort By */}
      <div className="form-group list-filter-item">
        <label htmlFor="sort_by">Sort By</label>
        <Input.Select
          name="sort_by"
          options={SORT_BY}
          value={formData.sort_by}
          onChange={setField}
        />
      </div>

      {/* Input for Coupon Count */}
      <div className="form-group list-filter-item" style={{ flexBasis: '70px' }}>
        <label htmlFor="count">Count</label>
        <Input.Select
          id="couponCount"
          name="count"
          options={COUNT}
          value={formData.count}
          onChange={setField}
        />
      </div>

      {/* Select for Coupon Display */}
      <div className="form-group list-filter-item">
        <label htmlFor="couponDisplay">Display on Checkout</label>
        <Input.Select
          name="display"
          options={COUPON_DISPLAY}
          value={formData.display}
          onChange={setField}
        />
      </div>

      <div className="form-group list-filter-item">
        <label htmlFor="source">Source</label>
        <Input.Select
          name="source"
          options={COUPON_SOURCES}
          value={formData.source}
          onChange={setField}
        />
      </div>
    </ListFilter>
  );
};

export default CouponFilters;
