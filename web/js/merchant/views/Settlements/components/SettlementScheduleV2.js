import React, { Fragment, useState } from 'react';
import { Box, Text } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { useI18Service } from 'common/i18';
import ModalHeader from 'common/ui/ModalHeader';
import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import { getCustomURL } from 'merchant/components/DocsLink';
import ShowWhen from 'merchant/components/ShowWhen';
import { isOmniChannelMerchant } from 'merchant/utils/omniUtils';
import HolidayModal from 'merchant/views/Settlements/Settlements/components/Modals/HolidayModal';
import {
  closeModal as fnCloseModal,
  openModal as fnOpenModal,
} from 'merchant_common/reducers/modals';

import EntitySchedule from './EntitySchedule';
import PaymentSchedule from './PaymentSchedule';
import { filterAndTransformInPersonSchedule } from './utils';

const paymentTypes = ['domestic', 'international'];
const specialScheduleNames = ['instant'];
const transferReversalCommunication =
  'The fund transfer happens internally as per the given schedule, the credit to linked accounts will happen as per the settlement schedule of the linked accounts.';

const renderPosCycleList = (schedules, refundSchedule) => {
  if (!schedules?.payment) return null;
  const filteredInPersonSchedule = filterAndTransformInPersonSchedule(schedules.payment);
  return (
    <ul>
      {schedules?.payment && (
        <li>
          Payments default settlement cycle
          {Object.entries(filteredInPersonSchedule).map(([key, val], index) => {
            return (
              <div key={index} className="payment-schedule-container">
                <div className="default-cycle schedule-row">
                  <div className="section capitalize">{key} Payments</div>
                  <div className="section capitalize text-right">
                    <strong>{val}</strong>
                  </div>
                </div>
              </div>
            );
          })}
          <div className="schedule-info">
            <span className="text-danger">*</span>T is the date of payment capture
          </div>
        </li>
      )}
      {refundSchedule && (
        <li>
          Other Settlement cycle
          <EntitySchedule entityType="refunds" schedule="Normal" />
        </li>
      )}
    </ul>
  );
};
const SettlementScheduleV2 = (props) => {
  const { closeModal, openModal, holidayList, config: settlementConfig, user } = props;
  const [showExample, setShowExample] = useState(false);

  const schedules = settlementConfig?.data?.config?.schedules;
  const refundSchedule = schedules?.refund?.default;
  const reversalSchedule = schedules?.reversal?.default;
  const transferSchedule = schedules?.transfer?.default;
  const _isOmniChannelMerchant = isOmniChannelMerchant(user);
  const { isConfigTagEnabled } = useI18Service();

  const toggleExample = () => {
    setShowExample(!showExample);

    if (!showExample) {
      analyticsTrackWithUserInfo({
        objectName: 'View Settlement',
        actionName: 'Example Clicked',
        screen: 'Settlements',
        properties: {
          page: 'Home Screen',
          settlements_experiment_name: user.isSettlementV3RevampEnabled ? 'v2' : 'v1',
          state: user.isTransacted ? 'Complete' : 'Empty',
          activation_status: user.activation_status,
          sessionId: window?.session_id,
          isL2Completed: user.isActivated,
          international_payments_enabled: user.international,
        },
      });
    }
  };

  const viewHolidayList = () => {
    openModal({
      size: 'small',
      component: <HolidayModal holidayList={holidayList} />,
    });
  };

  const showScheduleInfoCommunication = () => {
    const showRefunds = !specialScheduleNames.includes(refundSchedule?.toLowerCase());
    const showReversals =
      user.isMarketplaceEnabled && !specialScheduleNames.includes(reversalSchedule?.toLowerCase());
    const showTransfers =
      user.isMarketplaceEnabled && !specialScheduleNames.includes(transferSchedule?.toLowerCase());
    return showRefunds || showReversals || showTransfers;
  };

  const renderCycleList = () => (
    <ul>
      {schedules?.payment && (
        <li>
          Payments default settlement cycle
          {paymentTypes.map((paymentType) => (
            <PaymentSchedule
              paymentType={paymentType}
              key={paymentType}
              schedules={schedules.payment}
            />
          ))}
          <div className="schedule-info">
            <span className="text-danger">*</span>T is the date of payment capture
          </div>
        </li>
      )}
      {(refundSchedule ||
        (user.isMarketplaceEnabled && (reversalSchedule || transferSchedule))) && (
        <li>
          Other Settlement cycle
          {refundSchedule && <EntitySchedule entityType="refunds" schedule={refundSchedule} />}
          {user.isMarketplaceEnabled && (
            <>
              {reversalSchedule && (
                <EntitySchedule
                  entityType="reversals"
                  schedule={reversalSchedule}
                  info={transferReversalCommunication}
                />
              )}
              {transferSchedule && (
                <EntitySchedule
                  entityType="transfers"
                  schedule={transferSchedule}
                  info={transferReversalCommunication}
                />
              )}
            </>
          )}
          {showScheduleInfoCommunication() && (
            <div className="schedule-info">
              <span className="text-danger">*</span>T is the date of initiation
            </div>
          )}
        </li>
      )}
    </ul>
  );

  return (
    <div>
      <ModalHeader title="Settlement Cycle" onCloseClick={closeModal} />
      <div className="modal-body">
        <div className="settlement-cycle-overflow-box">
          <Box padding="11px 342px 10px 25px" backgroundColor="surface.background.gray.subtle">
            <Text weight="semibold" color="surface.text.gray.subtle">
              Online
            </Text>
          </Box>
          {renderCycleList()}
          {_isOmniChannelMerchant ? (
            <Fragment>
              <Box
                marginTop="spacing.4"
                padding="11px 342px 10px 25px"
                backgroundColor="surface.background.gray.subtle"
                whiteSpace="nowrap"
              >
                <Text weight="semibold" color="surface.text.gray.subtle">
                  In Person
                </Text>
              </Box>
              {renderPosCycleList(schedules, refundSchedule)}
            </Fragment>
          ) : null}
          <div className="settlement-example">
            <p>
              <strong>Note:</strong> <span className="yellow">Bank holidays</span> aren’t counted as
              working days.
              <br />
              <span onClick={toggleExample} className="btn-link">
                {showExample ? 'Hide' : 'View'} Example
                <i className={`i i-arrow-${showExample ? 'up' : 'down'}`} />
              </span>
            </p>
            {showExample && (
              <>
                <h6 className="grey">Assume settlement schedule for a payment is T+3</h6>
                <img
                  src="https://cdn.razorpay.com/static/assets/settlements/settlement-example-new.svg"
                  alt="settlement holiday example"
                  className="settlement-example-image"
                />
              </>
            )}
          </div>
        </div>
        <div className="settlement-cycle-btn-container">
          <ShowWhen additionalCondition={() => !isConfigTagEnabled('settlements.holiday_list')}>
            <div className="button-wrapper mr-8">
              <button onClick={viewHolidayList} className="btn btn-default full-width no-margin">
                List of Bank Holidays
              </button>
            </div>
          </ShowWhen>
          <ShowWhen additionalCondition={() => !isConfigTagEnabled('settlements.settlement_guide')}>
            <div className="button-wrapper ml-8">
              <a
                href={getCustomURL('https://razorpay.com/settlement')}
                target="_blank"
                rel="noopener noreferrer"
              >
                <button type="button" className="btn btn-primary full-width no-margin">
                  Settlement Guide
                </button>
              </a>
            </div>
          </ShowWhen>
        </div>
      </div>
    </div>
  );
};

const mapStateToProps = (state) => ({ ...state.settlement, user: state.session.user });

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators({ closeModal: fnCloseModal, openModal: fnOpenModal }, dispatch);
};

export default connect(mapStateToProps, mapDispatchToProps)(SettlementScheduleV2);
