import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { useCallback, useEffect, useState } from 'react';
import GenericPanel, {
  PanelTopbar,
  PanelBody,
  PanelFooter,
} from 'merchant/components/Home/GenericPanel';
import LastUpdated from 'merchant/components/Home/LastUpdated';
import {
  action,
  ip,
  shippedOrders,
  rtoOrders,
  rtoRank,
} from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/RtoBy/ipAddress/cellItem';
import ConfirmModal from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/RtoBy/components/ConfirmModal';
import PaginatedTable from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/RtoBy/components/PaginatedTable';

import { openModal, closeModal } from 'merchant_common/reducers/modals';
import {
  blockValue,
  unblockValue,
  fetchWidgetData,
} from 'merchant/reducers/magicCheckout/rtoAnalytics/actions';
import { showNotification } from 'merchant_common/reducers/notifications';

import {
  getWidgetData,
  onRequestCountChange,
} from 'merchant/views/MagicCheckout/RTOAnalytics/utils';

import { NO_GRAPH_DATA, BREAKDOWN } from 'merchant/views/MagicCheckout/RTOAnalytics/constants';

const IpAddress = ({
  widgetData,
  openModal,
  closeModal,
  blockValue,
  unblockValue,
  showNotification,
  fetchWidgets,
}) => {
  const [requestCount, setRequestCount] = useState(0);

  const { loading, data, updatedAt } = widgetData;

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
            message: 'IP address blocked successfully',
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
            message: 'IP address unblocked successfully',
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

  const fetchData = useCallback(() => {
    getWidgetData('rto_by_ip', BREAKDOWN.lifetime, 0, 0, fetchWidgets, setRequestCount);
  }, [fetchWidgets]);

  useEffect(() => {
    if (fetchWidgets) {
      fetchData();
    }
  }, [fetchWidgets]);

  useEffect(() => {
    onRequestCountChange(requestCount, fetchData, setRequestCount);
  }, [requestCount]);

  const onCTAClick = useCallback(
    (ip, isBlocked) => {
      openModal({
        size: 'small',
        className: 'rto-cause-modal',
        component: (
          <ConfirmModal
            value={ip}
            isBlocked={isBlocked}
            closeModal={closeModal}
            modalSource="IP address"
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
      isLoading={loading}
    >
      <PanelTopbar>
        <>
          <p className="panel-topbar-heading">RTO orders by IP address</p>
          <p className="panel-heading-subtext">
            Blocking or unblocking will only affect COD orders for the IP address chosen.
          </p>
        </>
      </PanelTopbar>
      <PanelBody
        customTitle={NO_GRAPH_DATA.customTitle}
        customSubtitle={NO_GRAPH_DATA.customSubtitle}
        id="rto-cause-body"
      >
        {data && data.length ? (
          <PaginatedTable
            rows={data}
            columns={[ip, shippedOrders, rtoOrders, rtoRank, action({ onClick: onCTAClick })]}
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
  widgetData: state.magicRTOAnalytics.rto_by_ip,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      openModal,
      closeModal,
      blockValue,
      unblockValue,
      showNotification,
      fetchWidgets: fetchWidgetData,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(IpAddress);
