import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import moment from 'moment';
import ListFilter from 'merchant/components/ListFilter';
import Input from 'common/new-ui/Input';
import DateRangePicker from 'common/ui/DateRangePicker';

import { updateFilters } from 'merchant/reducers/magicCheckout/prepayCOD/orderConversionTab/actions';

import {
  RISK_TIERS,
  DATE_RANGE_PRESETS,
  MIN_START_DATE,
} from 'merchant/views/MagicCheckout/CODOrdersTab/constants';

import {
  PAYMENT_STATUS,
  COUNT,
} from 'merchant/views/MagicCheckout/CODToPrepaid/CODToPrepaidLinks/constants';

const defaultPreset = 0;

const OrderFilters = (props) => {
  const {
    formName,
    onSubmitHandler,
    resetHandler,
    orderFiltersData,
    onDatesChange,
    updateFilters,
    showRTORisk,
    additionalClass,
  } = props;

  const { id, receipt, riskTier, count, selectedPresetFromParent, paymentLinkStatus } =
    orderFiltersData || {};

  const setField = (e) => {
    const { name, value } = e.target;
    updateFilters({
      [name]: value.trim(),
      skip: 0,
    });
  };

  const onSelectPreset = (selectedPreset) => {
    updateFilters({
      selectedPresetFromParent: selectedPreset,
    });
  };

  const isOutsideRange = (day) => day.isAfter(moment()) || day.isBefore(moment(MIN_START_DATE));

  return (
    <>
      <ListFilter
        form={`${formName}-form`}
        onSubmit={onSubmitHandler}
        resetHandler={resetHandler}
        additionalClass={additionalClass}
      >
        <div className="form-group list-filter-item">
          <label htmlFor="order-id">Razorpay Order Id</label>
          <Input
            id="order-id"
            type="text"
            name="id"
            className="form-control input-sm"
            value={id}
            onChange={setField}
            autoComplete="off"
          />
        </div>
        <div className="form-group list-filter-item">
          <label htmlFor="receipt">Receipt</label>
          <Input
            id="receipt"
            type="text"
            name="receipt"
            className="form-control input-sm"
            value={receipt}
            onChange={setField}
            autoComplete="off"
          />
        </div>
        {showRTORisk && (
          <div className="form-group list-filter-item">
            <label htmlFor="riskTier">RTO Risk</label>
            <Input.Select
              id="riskTier"
              name="riskTier"
              options={RISK_TIERS}
              value={riskTier}
              onChange={setField}
            />
          </div>
        )}
        <div className="form-group datepicker-group">
          <label htmlFor="duration">Duration</label>
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
        <div className="form-group list-filter-item">
          <label htmlFor="paymentStatus">Conversion Status</label>
          <Input.Select
            id="paymentLinkStatus"
            name="paymentLinkStatus"
            options={PAYMENT_STATUS}
            value={paymentLinkStatus}
            onChange={setField}
          />
        </div>
        <div className="form-group list-filter-item count">
          <label htmlFor="count">Count</label>
          <Input.Select id="count" name="count" options={COUNT} value={count} onChange={setField} />
        </div>
      </ListFilter>
    </>
  );
};

const mapStateToProps = (state) => ({
  orderFiltersData: state.magicPrepayCODOrders,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      updateFilters,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(OrderFilters);
