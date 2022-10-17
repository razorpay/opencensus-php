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
import MultiSelectHeader from 'merchant/views/MagicCheckout/CODOrdersTab/common/MultiSelectHeader';
import OrderFilters from 'merchant/views/MagicCheckout/CODOrdersTab/common/OrderFilters';
import EmptyComponent from 'merchant/views/MagicCheckout/CODOrdersTab/common/EmptyComponent';
import {
  fetchCODOrders as fetchOrders,
  setTimeRange as setDate,
  reviewCODOrders as reviewOrders,
  updateFilters as setFilters,
} from 'merchant/reducers/magicCheckout/codOrders/action';
import * as ModalActions from 'merchant_common/reducers/modals';
import { showNotification as displayNotification } from 'merchant_common/reducers/notifications';
import {
  confirmReview,
  sortDateUtil,
  sortRiskTierUtil,
  showResultNotification,
  getPresetsValue,
} from 'merchant/views/MagicCheckout/CODOrdersTab/utils';
import {
  DATE_RANGE_PRESETS,
  REVIEWED_ORDERS_CATEGORY,
} from 'merchant/views/MagicCheckout/CODOrdersTab/constants';

const OnHoldOrdersTab = (props) => {
  const {
    codOrdersData,
    fetchCODOrders,
    setTimeRange,
    reviewCODOrders,
    openModal,
    showNotification,
    updateFilters,
    closeModal,
  } = props;

  const { id, receipt, riskTier, count, from, to, items, loading, skip, hasMoreOrders } =
    codOrdersData || {};

  const [itemsArray, setItemsArray] = useState(items);
  const [isCheckedAll, setIsCheckedAll] = useState(false);
  const [isMultiSelectDisabled, setIsMultiSelectDisabled] = useState(false);
  const [isChecked, setIsChecked] = useState(new Set());

  useEffect(() => {
    if (fetchCODOrders)
      fetchCODOrders({
        count: 25,
        skip: 0,
        review_status: REVIEWED_ORDERS_CATEGORY.hold,
      });
  }, [fetchCODOrders]);

  useEffect(() => {
    setIsChecked(new Set());
    setIsMultiSelectDisabled(false);
  }, [items]);

  useEffect(() => {
    setIsCheckedAll(isChecked.size && isChecked.size === items.length);
    const newArray = items.map((item) => {
      if (item.review_status && item.review_status !== REVIEWED_ORDERS_CATEGORY.hold[0]) {
        setIsMultiSelectDisabled(true);
      }

      return isChecked.has(item.id) ? { ...item, rowClass: ' selected' } : item;
    });
    setItemsArray(newArray);
  }, [isChecked, items]);

  const onSubmitHandler = useCallback(() => {
    setIsMultiSelectDisabled(false);
    fetchCODOrders({
      id,
      receipt,
      risk_tier: riskTier,
      from,
      to,
      count,
      skip,
      review_status: REVIEWED_ORDERS_CATEGORY.hold,
    });
  }, [fetchCODOrders, codOrdersData]);

  const resetHandler = useCallback(() => {
    setIsMultiSelectDisabled(false);
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

  const onConfirm = useCallback(
    (orderId, reviewType) => {
      const promise = reviewCODOrders({
        action: reviewType,
        id: orderId ? [orderId] : Array.from(isChecked),
      });

      showResultNotification({ promise, showNotification, closeModal, isChecked, reviewType });
    },
    [reviewCODOrders, showNotification, closeModal, isChecked],
  );

  const onReview = useCallback(
    (orderId = null, reviewType) => {
      confirmReview({
        orderId,
        reviewType,
        reviewCODOrders,
        showNotification,
        isChecked,
        openModal,
        closeModal,
        onConfirm,
      });
    },
    [reviewCODOrders, showNotification, isChecked, openModal, closeModal],
  );

  const onChecked = useCallback(
    (e) => {
      const { id, checked } = e.target;
      if (checked) setIsChecked((prevState) => new Set(prevState.add(id)));
      else
        setIsChecked((prev) => {
          const newSet = new Set(prev);
          newSet.delete(id);
          return newSet;
        });
    },
    [isChecked],
  );

  const onMutilSelect = useCallback(() => {
    if (isCheckedAll) {
      setIsChecked(new Set());
      setIsCheckedAll(!isCheckedAll);
      return;
    }

    setIsCheckedAll(!isCheckedAll);
    items.map((item) => setIsChecked((prevState) => new Set(prevState.add(item.id))));
  }, [isCheckedAll, isChecked, items]);

  const paginate = useCallback(
    (params) => {
      setIsMultiSelectDisabled(false);

      updateFilters({
        count: params.count,
        skip: params.skip,
      });

      return fetchCODOrders({
        ...params,
        from,
        to,
        review_status: REVIEWED_ORDERS_CATEGORY.hold,
      });
    },
    [updateFilters, fetchCODOrders, codOrdersData],
  );

  return (
    <div className="orders-container onHold-orders-container">
      <OrderFilters
        onSubmitHandler={onSubmitHandler}
        resetHandler={resetHandler}
        onDatesChange={onDatesChange}
      />
      <MultiSelectHeader
        onSelect={onMutilSelect}
        checked={isCheckedAll}
        isChecked={isChecked}
        onReview={onReview}
        disableMultiSelect={!itemsArray.length || isMultiSelectDisabled}
        hideHold
      />
      <DataTable
        title="orders"
        columns={[
          orderId(onChecked, isChecked),
          razorpayId,
          date(sortDate),
          reviewedBy,
          rtoRisk(sortRiskTier),
          actions(onReview, isChecked),
        ]}
        items={itemsArray}
        customClass="magic-cod-orders-table onhold-orders-table"
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
      ...ModalActions,
      fetchCODOrders: fetchOrders,
      setTimeRange: setDate,
      showNotification: displayNotification,
      reviewCODOrders: reviewOrders,
      updateFilters: setFilters,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(OnHoldOrdersTab);
