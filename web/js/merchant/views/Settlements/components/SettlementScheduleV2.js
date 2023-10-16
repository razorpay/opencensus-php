import React, { useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { useI18Service } from 'common/i18';
import {
  closeModal as fnCloseModal,
  openModal as fnOpenModal,
} from 'merchant_common/reducers/modals';
import ModalHeader from 'common/ui/ModalHeader';
import { getCustomURL } from 'merchant/components/DocsLink';
import HolidayModal from 'merchant/views/Settlements/Settlements/components/Modals/HolidayModal';
import PaymentSchedule from './PaymentSchedule';
import EntitySchedule from './EntitySchedule';
import ShowWhen from 'merchant/components/ShowWhen';
import { analyticsTrackWithUserInfo } from 'common/utils/analytics';

const paymentTypes = ['domestic', 'international'];
const specialScheduleNames = ['instant']; // these schedules names doesn't have T in their name so we don't want to communicate the info on T

const transferReversalCommunication =
  'The fund transfer happens internally as per the given schedule, the credit to linked accounts will happen as per the settlement schedule of the linked accounts.';

const SettlementScheduleV2 = (props) => {
  const { closeModal, openModal, holidayList, config: settlementConfig, user } = props;
  const [showExample, setShowExample] = useState(false);

  const schedules = settlementConfig?.data?.config?.schedules;
  const refundSchedule = schedules?.refund?.default;
  const reversalSchedule = schedules?.reversal?.default;
  const transferSchedule = schedules?.transfer?.default;
  const { isConfigTagEnabled } = useI18Service();

  const toggleExample = () => {
    const { user } = props;
    setShowExample(!showExample);

    // instrument only view clicks
    if (showExample === false)
      analyticsTrackWithUserInfo({
        objectName: 'View Settlement',
        actionName: 'Example Clicked',
        screen: 'Settlements',
        properties: {
          page: 'Home Screen',
          settlements_experiment_name: user.isSettlementV3RevampEnabled ? 'v2' : 'v1',
          state: user.isTransacted ? 'Complete' : 'Empty',
          activation_status: user.activation_status,
          sessionId: window?.session_id ? window.session_id : undefined,
          isL2Completed: user.isActivated && true,
          international_payments_enabled: user.international,
        },
      });
  };

  const viewHolidayList = () => {
    openModal({
      size: 'small',
      component: <HolidayModal holidayList={holidayList} />,
    });
  };

  // For non-special names we need to show information about T
  const showScheduleInfoCommunication = () => {
    const showRefunds = !specialScheduleNames.includes(refundSchedule?.toLowerCase());
    // Consider transfer and reversal only for Route merchants
    const showReversals =
      user.isMarketplaceEnabled && !specialScheduleNames.includes(reversalSchedule?.toLowerCase());
    const showTransfers =
      user.isMarketplaceEnabled && !specialScheduleNames.includes(transferSchedule?.toLowerCase());
    // If atleast one schedule name has T we should communicate the info about T
    return showRefunds || showReversals || showTransfers;
  };

  return (
    <div>
      <ModalHeader title="Settlement Cycle" onCloseClick={closeModal} />
      <div className="modal-body">
        <div className="settlement-cycle-overflow-box">
          <ul>
            {schedules?.payment && (
              <li>
                Payments default settlement cycle
                {paymentTypes.map((paymentType) => {
                  return (
                    <PaymentSchedule
                      paymentType={paymentType}
                      key={paymentType}
                      schedules={schedules.payment}
                    />
                  );
                })}
                <div className="schedule-info">
                  <span className="text-danger">*</span>T is the date of payment capture
                </div>
              </li>
            )}
            {(refundSchedule ||
              (user.isMarketplaceEnabled && (reversalSchedule || transferSchedule))) && (
              <li>
                Other Settlement cycle
                {schedules?.refund?.default && (
                  <EntitySchedule entityType="refunds" schedule={refundSchedule} />
                )}
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
