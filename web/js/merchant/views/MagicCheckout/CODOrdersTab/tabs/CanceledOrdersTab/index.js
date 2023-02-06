import { useCallback, useEffect, useState } from 'react';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import DataTable from 'common/ui/Table/DataTable';
import {
  orderId,
  razorpayId,
  date,
  actions,
  rtoRisk,
  reviewedBy,
} from 'merchant/views/MagicCheckout/CODOrdersTab/common/CellItems';
import OrderFilters from 'merchant/views/MagicCheckout/CODOrdersTab/common/OrderFilters';
import EmptyComponent from 'merchant/views/MagicCheckout/CODOrdersTab/common/EmptyComponent';
import {
  fetchCODOrders as fetchOrders,
  setTimeRange as setDate,
  updateFilters as setFilters,
} from 'merchant/reducers/magicCheckout/codOrders/action';
import {
  sortDateUtil,
  sortRiskTierUtil,
  getPresetsValue,
} from 'merchant/views/MagicCheckout/CODOrdersTab/utils';
import {
  REVIEWED_ORDERS_CATEGORY,
  DATE_RANGE_PRESETS,
} from 'merchant/views/MagicCheckout/CODOrdersTab/constants';

const CanceledOrdersTab = (props) => {
  const { codOrdersData, fetchCODOrders, setTimeRange, updateFilters } = props;

  const {
    id,
    receipt,
    riskTier,
    count,
    from,
    to,
    items,
    loading,
    skip,
    hasMoreOrders,
    reviewMode,
  } = codOrdersData;

  const [itemsArray, setItemsArray] = useState([]);

  useEffect(() => {
    if (fetchCODOrders)
      fetchCODOrders({
        count: 25,
        skip: 0,
        review_status: REVIEWED_ORDERS_CATEGORY.canceled,
      });
  }, [fetchCODOrders]);

  useEffect(() => {
    setItemsArray(items);
  }, [items]);

  const onSubmitHandler = useCallback(() => {
    fetchCODOrders({
      id,
      receipt,
      risk_tier: riskTier,
      from,
      to,
      count,
      skip,
      review_status: REVIEWED_ORDERS_CATEGORY.canceled,
      review_mode: reviewMode,
    });
  }, [fetchCODOrders, codOrdersData]);

  const resetHandler = useCallback(() => {
    const presets = getPresetsValue(DATE_RANGE_PRESETS);
    updateFilters({
      id: '',
      receipt: '',
      riskTier: '',
      count: 25,
      skip: 0,
      from: '',
      to: '',
      selectedPresetFromParent: presets[0],
      reviewMode: '',
    });
  }, [updateFilters]);

  const onDatesChange = useCallback(
    (from, to) => {
      setTimeRange(from, to);
    },
    [setTimeRange],
  );

  const sortDate = useCallback(
    (sortType) => {
      const newArray = sortDateUtil(itemsArray, sortType);
      setItemsArray(newArray);
    },
    [itemsArray],
  );

  const sortRiskTier = useCallback(
    (sortType) => {
      const newArray = sortRiskTierUtil(itemsArray, sortType);
      setItemsArray(newArray);
    },
    [itemsArray],
  );

  const paginate = useCallback(
    (params) => {
      updateFilters({
        count: params.count,
        skip: params.skip,
      });

      return fetchCODOrders({
        ...params,
        from,
        to,
        review_status: REVIEWED_ORDERS_CATEGORY.canceled,
      });
    },
    [updateFilters, fetchCODOrders, codOrdersData],
  );

  return (
    <div className="orders-container canceled-orders-container">
      <OrderFilters
        onSubmitHandler={onSubmitHandler}
        resetHandler={resetHandler}
        onDatesChange={onDatesChange}
        showReviewModeFilter
      />
      <DataTable
        title="orders"
        columns={[
          orderId(),
          razorpayId,
          date(sortDate),
          reviewedBy,
          rtoRisk(sortRiskTier),
          actions(),
        ]}
        items={itemsArray}
        customClass="magic-cod-orders-table canceled-orders-table"
        loading={loading}
        skip={skip}
        count={count}
        paginate={paginate}
        EmptyComponent={EmptyComponent}
        hasMoreData={hasMoreOrders}
      />
    </div>
  );
};

const mapStateToProps = (state) => ({
  codOrdersData: state.magicCODOrders,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      fetchCODOrders: fetchOrders,
      setTimeRange: setDate,
      updateFilters: setFilters,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(CanceledOrdersTab);
