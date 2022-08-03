import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { useCallback, useEffect, useState } from 'react';
import GenericPanel, {
  PanelTopbar,
  PanelBody,
  PanelFooter,
} from 'merchant/components/Home/GenericPanel';
import LastUpdated from 'merchant/components/Home/LastUpdated';
import PaginatedTable from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/RtoBy/components/PaginatedTable';
import ConfirmModal from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/RtoBy/components/ConfirmModal';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import {
  action,
  pincode,
  shippedOrders,
  rtoOrders,
  rtoPercent,
} from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/RtoBy/pincodes/cellItem';
import { blockValue, unblockValue } from 'merchant/reducers/magicCheckout/rtoAnalytics/actions';
import { showNotification } from 'merchant_common/reducers/notifications';
import { NO_GRAPH_DATA } from 'merchant/views/MagicCheckout/RTOAnalytics/constants';

const Pincodes = ({
  widgetData,
  openModal,
  closeModal,
  blockValue,
  unblockValue,
  showNotification,
}) => {
  const [tableData, setTableData] = useState(null);
  const { data, updatedAt } = widgetData;

  const onConfirmBlock = useCallback(
    (value, modalSource) => {
      blockValue({
        cod_eligibility_attributes: [
          {
            attribute_type: modalSource === 'pincode' ? 'zipcode' : 'ip',
            attribute_value: value,
          },
        ],
      })
        .then(() => {
          showNotification({
            type: 'success',
            message: 'Zipcode blocked successfully',
          });
          closeModal();
        })
        .catch(() => {
          showNotification({
            type: 'success',
            message: 'Something went wrong, please try agai',
          });
          closeModal();
        });
    },
    [closeModal, blockValue, showNotification],
  );

  const onConfirmUnblock = useCallback(
    (value, modalSource) => {
      unblockValue({
        attribute_value: value,
        attribute_type: modalSource === 'pincode' ? 'zipcode' : 'ip',
      })
        .then(() => {
          showNotification({
            type: 'success',
            message: 'Zipcode unblocked successfully',
          });
          closeModal();
        })
        .catch(() => {
          showNotification({
            type: 'error',
            message: 'Something went wrong, please try again',
          });
          closeModal();
        });
    },
    [closeModal, unblockValue, showNotification],
  );

  useEffect(() => {
    // sort in decreasing order
    setTableData(data?.sort((a, b) => b.rto_order - a.rto_order));
  }, [widgetData, data, setTableData]);

  const onCTAClick = useCallback(
    (zipcode, isBlocked) => {
      openModal({
        size: 'small',
        className: 'rto-cause-modal',
        component: (
          <ConfirmModal
            value={zipcode}
            isBlocked={isBlocked}
            closeModal={closeModal}
            modalSource="pincode"
            onConfirmClick={isBlocked ? onConfirmUnblock : onConfirmBlock}
          />
        ),
      });
    },
    [openModal, closeModal, onConfirmBlock, onConfirmUnblock],
  );

  return (
    <GenericPanel
      className="analytics-panel rto-cause"
      hasNoData={!data || data.length === 0}
      isLoading={false}
    >
      <PanelTopbar>RTO Orders by pincodes</PanelTopbar>
      <PanelBody
        customTitle={NO_GRAPH_DATA.customTitle}
        customSubtitle={NO_GRAPH_DATA.customSubtitle}
      >
        {tableData && tableData.length ? (
          <PaginatedTable
            rows={tableData}
            columns={[
              pincode,
              shippedOrders,
              rtoOrders,
              rtoPercent,
              action({ onClick: onCTAClick }),
            ]}
          />
        ) : null}
      </PanelBody>
      <PanelFooter>
        <LastUpdated at={updatedAt} customIcon="i-clock" />
      </PanelFooter>
    </GenericPanel>
  );
};

const mapStateToProps = (state) => ({
  widgetData: state.magicRTOAnalytics.rto_by_zipcode,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      openModal,
      closeModal,
      blockValue,
      unblockValue,
      showNotification,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(Pincodes);
