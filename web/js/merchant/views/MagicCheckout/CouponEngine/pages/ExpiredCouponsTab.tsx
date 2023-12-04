import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators, Dispatch } from 'redux';

// ui imports
import DataTable from 'common/ui/Table/DataTable';
import {
  couponCodeWithoutCheckBox,
  couponType,
  checkoutDisplayStatus,
  couponUseageCount,
  couponStatus,
  actions,
  couponSource,
  couponDescription,
} from 'merchant/views/MagicCheckout/CouponEngine/components/CellItems';
import OrderFilters from 'merchant/views/MagicCheckout/CouponEngine/components/CouponFilters';
import EmptyComponent from 'merchant/views/MagicCheckout/CouponEngine/components/EmptyComponent';

// api imports
import { listCoupons } from 'merchant/views/MagicCheckout/CouponEngine/api';

// helper imports
import { showNotification } from 'merchant_common/reducers/notifications';

interface PropsFromRedux {
  showNotification: (notification: any) => void;
}

type ExpiredCoupons = PropsFromRedux;

const initialFiltersState = {
  type: 'all',
  code: '',
  status: 'expired',
  sort_by: 'date-desc',
  skip: 0,
  count: 10,
  display: 'all',
};

const ExpiredCouponsTab: React.FC<ExpiredCoupons> = ({ showNotification }) => {
  const [allCoupons, setAllCoupons] = useState([]);
  const [isLoading, setIsLoading] = useState(false);
  const [filters, setFilters] = useState(initialFiltersState);

  const fetchExpiredCouponsData = async () => {
    setIsLoading(true);
    try {
      const res = await listCoupons(filters);
      setAllCoupons(res.data.coupons || []);
    } catch (err) {
      showNotification({
        type: 'error',
        message: 'Something went wrong. Please try again later.',
      });
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchExpiredCouponsData();
  }, [filters]);

  const handleSubmit = (data: any) => {
    setFilters((prevFilters) => ({ ...prevFilters, ...data }));
  };

  const handleReset = () => {
    setFilters(initialFiltersState);
  };

  const paginate = (params: { skip: number; count: number }) => {
    setFilters((prevFilters) => ({ ...prevFilters, skip: params.skip, count: params.count }));
  };

  return (
    <div className="orders-container review-orders-container">
      <OrderFilters onSubmitHandler={handleSubmit} resetHandler={handleReset} tabName="expired" />
      <DataTable
        title="coupons"
        columns={[
          couponCodeWithoutCheckBox,
          couponDescription,
          couponStatus,
          couponType,
          couponSource,
          checkoutDisplayStatus,
          couponUseageCount,
          actions(),
        ]}
        items={allCoupons}
        customClass="magic-coupons-table"
        loading={isLoading}
        skip={filters.skip}
        count={filters.count}
        paginate={paginate}
        EmptyComponent={EmptyComponent}
      />
    </div>
  );
};

const mapDispatchToProps = (dispatch: Dispatch) =>
  bindActionCreators(
    {
      showNotification,
    },
    dispatch,
  );

export default connect(null, mapDispatchToProps)(ExpiredCouponsTab);
