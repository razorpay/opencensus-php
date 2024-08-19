import React, { useEffect, useState, useCallback, useContext } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators, Dispatch } from 'redux';

// ui imports
import DataTable from 'common/ui/Table/DataTable';
import { DataTableWrapper } from 'merchant/views/MagicCheckout/CouponEngine/styles/DataTableElements';
import EmptyComponent from 'merchant/views/MagicCheckout/CouponEngine/components/EmptyComponent';
import MultiSelectHeader from 'merchant/views/MagicCheckout/CouponEngine/components/MultiSelectHeader/MultiSelectHeader';
import {
  couponCode,
  couponType,
  checkoutDisplayStatus,
  couponUseageCount,
  couponStatus,
  actions,
  couponDescription,
  couponSource,
} from 'merchant/views/MagicCheckout/CouponEngine/components/CellItems';
import OrderFilters from 'merchant/views/MagicCheckout/CouponEngine/components/CouponFilters';

// api imports
import { listCoupons } from 'merchant/views/MagicCheckout/CouponEngine/api';

// helper imports
import { showNotification } from 'merchant_common/reducers/notifications';
import { ModalContext } from 'merchant/views/MagicCheckout/CouponEngine/context';

// type imports
import { GenericCouponsProps, CheckedItem } from 'merchant/views/MagicCheckout/CouponEngine/types';

import { COUPON_NAMES } from 'merchant/views/MagicCheckout/CouponEngine/constants';

const GenericCoupons: React.FC<GenericCouponsProps> = ({
  showNotification,
  initialFilters,
  tabName = '',
  isRcodEnabled,
}) => {
  const [checkedIds, setCheckedIds] = useState<CheckedItem[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [filters, setFilters] = useState(initialFilters);
  const {
    setAllCouponsList,
    allCouponsList,
    hasCouponScreenLoadedOnce,
    setHasCouponScreenLoadedOnce,
  } = useContext(ModalContext);

  const fetchAllCouponsData = async () => {
    setIsLoading(true);
    try {
      setCheckedIds([]);
      setAllCouponsList([]);
      const res = await listCoupons(filters);
      const coupons = res?.data?.coupons;
      /**
       * For MagicX , Free shipping coupon type and bulk order discount is not available
       */
      const excludedCouponsForMagicX = [COUPON_NAMES.FREE_SHIPPING, COUPON_NAMES.BULK_ORDER];
      const couponsList = isRcodEnabled
        ? coupons?.filter((coupon) => !excludedCouponsForMagicX.includes(coupon.type))
        : coupons;
      setAllCouponsList(couponsList || []);
    } catch (err) {
      showNotification({
        type: 'error',
        message: 'Something went wrong. Please try again later.',
      });
      setAllCouponsList([]);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    // this testMode will be used in case for testcases
    if (!hasCouponScreenLoadedOnce || initialFilters?.testMode) {
      fetchAllCouponsData();
    } else {
      setHasCouponScreenLoadedOnce(false);
    }
  }, [filters]);

  const handleSubmit = (data: any) => {
    setFilters((prevFilters) => ({ ...prevFilters, ...data }));
  };

  const handleReset = () => {
    setFilters(initialFilters);
  };

  const onChecked = (e: React.ChangeEvent<HTMLInputElement>, coupon: any) => {
    const { id, checked: isChecked } = e.target;
    setCheckedIds((prevIds) =>
      isChecked ? [...prevIds, { ...coupon }] : prevIds.filter((item) => item.id !== id),
    );
  };

  const onMultiSelect = useCallback(() => {
    if (checkedIds?.length === allCouponsList?.length) {
      setCheckedIds([]);
    } else {
      const allCouponIds = allCouponsList.map((coupon: any) => ({
        ...coupon,
      }));

      setCheckedIds(allCouponIds);
    }
  }, [checkedIds, allCouponsList]);

  const paginate = (params: { skip: number; count: number }) => {
    setFilters((prevFilters) => ({ ...prevFilters, skip: params.skip, count: params.count }));
  };

  const updateCouponsCb = (coupon: any, status: string) => {
    const updatedList = allCouponsList.map((couponItem) => {
      if (couponItem.id === coupon.id) {
        return {
          ...couponItem,
          status,
        };
      }
      return couponItem;
    });
    setAllCouponsList(updatedList);
  };

  return (
    <div className="orders-container review-orders-container">
      {tabName !== 'expired' && (
        <>
          <OrderFilters
            onSubmitHandler={handleSubmit}
            resetHandler={handleReset}
            tabName={tabName}
            isRcodEnabled={isRcodEnabled}
          />
          <MultiSelectHeader
            onSelect={onMultiSelect}
            checked={checkedIds?.length === allCouponsList?.length && !!allCouponsList?.length}
            disableMultiSelect={!allCouponsList?.length || isLoading}
            showAggregatedActions={!!checkedIds?.length}
            checkedIds={checkedIds}
            setCheckedIds={setCheckedIds}
            updateCouponsCb={updateCouponsCb}
          />
        </>
      )}
      <DataTableWrapper>
        <DataTable
          title="coupons"
          columns={[
            couponCode(onChecked, checkedIds),
            couponDescription,
            couponStatus,
            couponType,
            couponSource,
            checkoutDisplayStatus,
            couponUseageCount,
            actions(updateCouponsCb),
          ]}
          items={allCouponsList || []}
          customClass="magic-coupons-table"
          loading={isLoading}
          skip={filters.skip}
          count={filters.count}
          paginate={paginate}
          EmptyComponent={EmptyComponent}
        />
      </DataTableWrapper>
    </div>
  );
};

const mapStateToProps = (state) => ({
  isRcodEnabled: state.magicCheckout.rcod,
});

const mapDispatchToProps = (dispatch: Dispatch) =>
  bindActionCreators(
    {
      showNotification,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(GenericCoupons);
