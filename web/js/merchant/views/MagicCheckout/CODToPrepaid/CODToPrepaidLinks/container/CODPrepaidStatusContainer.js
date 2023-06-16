import { useState, useEffect } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import OrderFilters from 'merchant/views/MagicCheckout/CODToPrepaid/CODToPrepaidLinks/components/OrderFilters';
import DataTable from 'common/ui/Table/DataTable';
import EmptyComponent from 'merchant/views/MagicCheckout/CODOrdersTab/common/EmptyComponent';
import {
  razorpayId,
  date,
  rtoRisk,
} from 'merchant/views/MagicCheckout/CODOrdersTab/common/CellItems';
import {
  paymentLinkOrderId as paymentOrderId,
  paymentLinkAction,
  paymentLinkStatus as paymentLinkStatusCol,
} from 'merchant/views/MagicCheckout/CODToPrepaid/CODToPrepaidLinks/components/CellItem';

import {
  fetchPrepayCODOrders as fetchOrders,
  setTimeRange as setDate,
  reviewPrepayCODOrders as reviewOrders,
  updateFilters as setFilters,
} from 'merchant/reducers/magicCheckout/prepayCOD/orderConversionTab/actions';

import * as ModalActions from 'merchant_common/reducers/modals';
import { showNotification as displayNotification } from 'merchant_common/reducers/notifications';
import {
  sortDateUtil,
  sortRiskTierUtil,
  getPresetsValue,
} from 'merchant/views/MagicCheckout/CODOrdersTab/utils';
import { openConfirmationModal } from 'merchant/views/MagicCheckout/CODToPrepaid/CODToPrepaidLinks/utils';

import { DATE_RANGE_PRESETS } from 'merchant/views/MagicCheckout/CODOrdersTab/constants';
import { PL_EXPIRE_CONFIRMATION_TEXT } from 'merchant/views/MagicCheckout/CODToPrepaid/CODToPrepaidLinks/constants';

const CODPrepaidStatusContainer = (props) => {
  const {
    manualReview,
    fetchPrepayCODOrders,
    codOrdersData,
    setTimeRange,
    showNotification,
    updateFilters,
    reviewPrepayCODOrders,
    openModal,
    closeModal,
  } = props;

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
    paymentLinkStatus,
  } = codOrdersData || {};

  const [itemsArray, setItemsArray] = useState(items);
  const [columns, setColumns] = useState([]);

  const dataTableAdditionalClass = !manualReview ? ' intelligence-table' : '';

  useEffect(() => {
    if (fetchPrepayCODOrders)
      fetchPrepayCODOrders({
        count: 25,
        skip: 0,
      });
  }, [fetchPrepayCODOrders]);

  useEffect(() => {
    setItemsArray(items);
  }, [items]);

  const sortDate = (sortType) => {
    const newArray = sortDateUtil(itemsArray, sortType);
    setItemsArray(newArray);
  };

  const sortRiskTier = (sortType) => {
    const newArray = sortRiskTierUtil(itemsArray, sortType);
    setItemsArray(newArray);
  };

  const onReview = (paymentLinkId = null, reviewType) => {
    const { heading, description, affirmLabel, abortLabel } = PL_EXPIRE_CONFIRMATION_TEXT;

    const modalInfo = {
      heading,
      description,
      abortLabel,
      affirmativeLabel: affirmLabel,
    };

    openConfirmationModal({
      openModal,
      modalInfo,
      paymentLinkId,
      reviewType,
      reviewPrepayCODOrders,
      showNotification,
      closeModal,
    });
  };

  useEffect(() => {
    if (manualReview) {
      setColumns([
        paymentOrderId,
        razorpayId,
        date(sortDate),
        rtoRisk(sortRiskTier),
        paymentLinkStatusCol,
        paymentLinkAction(onReview),
      ]);
    } else {
      setColumns([
        paymentOrderId,
        razorpayId,
        date(sortDate),
        paymentLinkStatusCol,
        paymentLinkAction(onReview),
      ]);
    }
  }, [manualReview, itemsArray]);

  const onDatesChange = (from, to) => {
    setTimeRange(from, to);
  };

  const onSubmitHandler = () => {
    fetchPrepayCODOrders({
      id,
      receipt,
      risk_tier: riskTier,
      from,
      to,
      count,
      skip,
      magic_pl_status: paymentLinkStatus,
    });
  };

  const resetHandler = () => {
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
      paymentLinkStatus: '',
    });
  };

  const paginate = (params) => {
    updateFilters({
      count: params.count,
      skip: params.skip,
    });

    return fetchPrepayCODOrders({
      ...params,
      from,
      to,
      id,
      receipt,
      risk_tier: riskTier,
      magic_pl_status: paymentLinkStatus,
    });
  };

  return (
    <div className="cod-prepaid-status-container">
      <OrderFilters
        formName="payment-status-filters"
        onSubmitHandler={onSubmitHandler}
        resetHandler={resetHandler}
        onDatesChange={onDatesChange}
        showRTORisk={manualReview}
        additionalClass={!manualReview && 'intelligence-filters'}
      />
      <DataTable
        title="payment-link-status"
        columns={columns}
        items={itemsArray}
        customClass={`magic-cod-orders-table${dataTableAdditionalClass}`}
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
  manualReview: state.magicCheckout?.cod_order_control,
  codOrdersData: state.magicPrepayCODOrders,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      ...ModalActions,
      fetchPrepayCODOrders: fetchOrders,
      setTimeRange: setDate,
      showNotification: displayNotification,
      reviewPrepayCODOrders: reviewOrders,
      updateFilters: setFilters,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(CODPrepaidStatusContainer);
