import { useCallback } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import moment from 'moment';
import ListFilter from 'merchant/components/ListFilter';
import Input from 'common/new-ui/Input';
import DateRangePicker from 'common/ui/DateRangePicker';
import CODAutomationBanner from 'merchant/views/MagicCheckout/CODOrdersTab/common/CODAutomationBanner';
import { updateFilters } from 'merchant/reducers/magicCheckout/codOrders/action';
import {
  RISK_TIERS,
  DATE_RANGE_PRESETS,
  MIN_START_DATE,
  REVIEW_MODE,
} from 'merchant/views/MagicCheckout/CODOrdersTab/constants';

const COUNT = [
  { label: '25', name: 25 },
  { label: '15', name: 15 },
  { label: '5', name: 5 },
];

const defaultPreset = 0;

const OrderFilters = ({
  formName = 'filters',
  onSubmitHandler,
  resetHandler,
  onDatesChange,
  orderFiltersData,
  updateFilters,
  showReviewModeFilter,
}) => {
  const { id, receipt, riskTier, count, selectedPresetFromParent, reviewMode } = orderFiltersData;

  const setField = useCallback(
    (e) => {
      const { name, value } = e.target;
      updateFilters({
        [name]: value.trim(),
        skip: 0,
      });
    },
    [updateFilters],
  );

  const onSelectPreset = useCallback(
    (selectedPreset) => {
      updateFilters({
        selectedPresetFromParent: selectedPreset,
      });
    },
    [updateFilters],
  );

  const isOutsideRange = (day) => day.isAfter(moment()) || day.isBefore(moment(MIN_START_DATE));

  return (
    <>
      <CODAutomationBanner />
      <ListFilter form={`${formName}-form`} onSubmit={onSubmitHandler} resetHandler={resetHandler}>
        <div className="form-group list-filter-item">
          <label htmlFor="magic-prepay-order-id">Razorpay Order Id</label>
          <input
            id="magic-prepay-order-id"
            type="text"
            name="id"
            className="form-control input-sm"
            value={id}
            onChange={setField}
          />
        </div>
        <div className="form-group list-filter-item">
          <label htmlFor="magic-prepay-receipt">Receipt</label>
          <input
            id="magic-prepay-receipt"
            type="text"
            name="receipt"
            className="form-control input-sm"
            value={receipt}
            onChange={setField}
          />
        </div>
        <div className="form-group list-filter-item">
          <label htmlFor="riskTier">RTO Risk</label>
          <Input.Select
            id="riskTier"
            name="riskTier"
            className="cod-review-input-dd"
            options={RISK_TIERS}
            value={riskTier}
            onChange={setField}
          />
        </div>
        <div className="form-group datepicker-group">
          <label>Duration</label>
          <DateRangePicker
            presets={DATE_RANGE_PRESETS}
            onDatesChange={onDatesChange}
            defaultPreset={defaultPreset}
            hideCustomPreset
            onSelectPreset={onSelectPreset}
            callPresetChangeOnCustomOption
            selectedPresetFromParent={selectedPresetFromParent}
            isOutsideRange={isOutsideRange}
            minStartDate={moment(MIN_START_DATE)}
          />
        </div>
        {showReviewModeFilter ? (
          <div className="form-group list-filter-item">
            <label htmlFor="reviewMode">Review Mode</label>
            <Input.Select
              id="reviewMode"
              name="reviewMode"
              className="cod-review-input-dd"
              options={REVIEW_MODE}
              value={reviewMode}
              onChange={setField}
            />
          </div>
        ) : null}
        <div className="form-group list-filter-item count">
          <label htmlFor="count">Count</label>
          <Input.Select
            id="count"
            name="count"
            className="cod-review-input-dd"
            options={COUNT}
            value={count}
            onChange={setField}
          />
        </div>
      </ListFilter>
    </>
  );
};

const mapStateToProps = (state) => ({
  orderFiltersData: state.magicCODOrders,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      updateFilters,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(OrderFilters);
