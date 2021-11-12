import React, { useEffect, useState } from 'react';
import TrackerLeftIllustration from './components/TrackerLeftIllus';
import TrackerStatus from './components/TrackerStatus';
import {
  bankNamesMap,
  RBL_STATUS,
  RBL_ACTIVATE,
  RBL_PAN_OR_APP,
  TRACKER_ERROR,
  derivedCaApplicationStatus,
  caApplicationStatus,
  caApplicationBlockedStatus,
} from './TrackerConstant';
import {
  ICICIKYCStatus,
  PAN_VERIFICATION_STATUSES,
  ICICI_PAN_OR_APP,
  ICICI_ACTIVATE,
  ICICI_STATUS,
  caApplicationBlockedICICIStatus,
} from './ICICITrackerStatus';
import NeoStoneTrackerShimmer from './components/NeoStoneTrackerShimmer';
import { merchantFetch } from 'merchant/utils/ajax';
import {
  getDerivedStatus,
  getErrorMessage,
  getICICIApplicationStatus,
  getICICIPanStatus,
} from '../common/utils';
import { showNotification as showNotificationProp } from 'merchant_common/reducers/notifications';
import { connect } from 'react-redux';
import { compose } from 'redux';
import { APIResponseType } from '../TypeDeclare/XCATypeDeclare';

const getICICIStatusRenderData = (active, state) => {
  let isFlag = false;
  return state.map((item) => {
    if (item?.bankStatus === 'sent_to_bank' && active === 'bank_processing') {
      item.highlight = true;
      item.bankStatus = 'bank_processing';
      item.code = 300;
      isFlag = true;
      item.subText =
        'The Bank RM has received your documents and we’re processing your application. You will be notified via SMS and Email when your application is approved soon.';
    } else if (item?.bankStatus === active) {
      item.highlight = true;
      item.code = 300;
      isFlag = true;
    } else {
      item.highlight = false;
      if (!isFlag) item.code = 200;
    }
    return item;
  });
};

const getStatusRenderData = (active, state) => {
  let isFlag = false;
  return state.map((item) => {
    if (item?.bankStatus === 'processing' && active === 'processed') {
      item.highlight = true;
      item.bankStatus = 'processed';
      item.code = 300;
      isFlag = true;
      item.subText =
        'Your Current Account is opened. Our team is working on activating your account on RazorpayX. This usually takes 3-5 working days.';
    } else if (item?.bankStatus === active) {
      item.highlight = true;
      item.code = 300;
      isFlag = true;
    } else {
      item.highlight = false;
      if (!isFlag) item.code = 200;
    }
    return item;
  });
};
const getActiveStatusOnly = (active, state, status = 300) => {
  return state
    .filter((item) => {
      return item?.bankStatus === active;
    })
    .map((item) => {
      item.highlight = true;
      item.code = status;
      return item;
    });
};
const getFirstStatus = (active, state) => {
  return state.filter((item) => {
    return item?.bankStatus === active;
  });
};

const NeoStoneTracker = ({ proceededBank, user, showNotification }) => {
  const [rblState, setRblState] = useState([...RBL_STATUS]);
  const [bankStatus, setBankStatus] = useState<APIResponseType>(null);
  const [iciciPanStatus, setICICIPanStatus] = useState<APIResponseType>(null);
  const [rblActiveStatus, setRblActiveStatus] = useState<Array<Record<string, unknown>>>([]);
  const [showState, setShowState] = useState<'loading' | 'error' | 'tracker'>('loading');
  const ICICIStatus: { applicationStatus: APIResponseType; panStatus: APIResponseType } = {
    applicationStatus: null,
    panStatus: null,
  };

  useEffect(() => {
    if (proceededBank === bankNamesMap.RBL) {
      merchantFetch({
        url: 'banking_accounts',
        mode: 'live',
      })
        .then((resp) => {
          const { status_code = '', data: { items = [] } = {} } = resp;
          if (status_code == 200) {
            const id = items[0]?.id;
            merchantFetch({
              url: `banking_accounts/${id}`,
              mode: 'live',
            }).then((response) => {
              setShowState('tracker');
              const { status_code: ba_status_code = '', data = {} } = response;

              if (ba_status_code == 200) {
                const activeRblStatus = getDerivedStatus(data) || null;
                // handle Error Status for RBL
                if (caApplicationBlockedStatus.includes(activeRblStatus)) {
                  const activeStatusOnly =
                    getActiveStatusOnly(activeRblStatus, TRACKER_ERROR, 400) || [];
                  setRblActiveStatus([...activeStatusOnly]);
                  setRblState([]);
                }
                // Pan failed OR in-progress
                else if (
                  activeRblStatus === derivedCaApplicationStatus.PAN_PENDING ||
                  activeRblStatus === derivedCaApplicationStatus.PAN_FAILED
                ) {
                  const firstTrackerStatus = getFirstStatus(activeRblStatus, RBL_PAN_OR_APP) || [];
                  setRblState([...firstTrackerStatus, ...RBL_STATUS]);
                  setRblActiveStatus([...firstTrackerStatus]);
                } // Complete your application
                else if (activeRblStatus === derivedCaApplicationStatus.PENDING) {
                  setRblState([RBL_PAN_OR_APP[0], ...RBL_STATUS]);
                  setRblActiveStatus([RBL_PAN_OR_APP[0]]);
                } // Telephonic verification
                else if (activeRblStatus === derivedCaApplicationStatus.RECIEVED) {
                  const trackerArr = [
                    { ...RBL_PAN_OR_APP[0], code: 200, highlight: false },
                    ...RBL_STATUS,
                  ];
                  const activeStatusOnly = getActiveStatusOnly(activeRblStatus, trackerArr) || [];
                  setRblState(trackerArr);
                  setRblActiveStatus([...activeStatusOnly]);
                } // Explore Current Account CTA
                else if (activeRblStatus === caApplicationStatus.ACTIVATED) {
                  const activeStatusOnly = getActiveStatusOnly(activeRblStatus, RBL_ACTIVATE) || [];
                  setRblActiveStatus([...activeStatusOnly]);
                  setRblState([]);
                } // In-person verification & documents pick up + Account activation
                else {
                  const trackerArr = [
                    { ...RBL_PAN_OR_APP[0], code: 200, highlight: false },
                    ...RBL_STATUS,
                  ];
                  const viewMoreTrackerList =
                    getStatusRenderData(activeRblStatus, trackerArr) || [];
                  const activeStatusOnly =
                    getActiveStatusOnly(activeRblStatus, viewMoreTrackerList) || [];
                  setRblState(viewMoreTrackerList);
                  setRblActiveStatus([...activeStatusOnly]);
                }
                setBankStatus(activeRblStatus);
              }
            });
          }
        })
        .catch((error) => {
          showNotification({
            type: 'error',
            message: getErrorMessage(error),
            hidePrevious: true,
          });
          setShowState('error');
        });
    } else if (proceededBank === bankNamesMap.ICICI) {
      const basBusinessID = user?.bas_business_id;

      const bankingApplicationFetchs = [
        merchantFetch({
          url: `merchant/banking_application/business/${basBusinessID}/applications`,
        }),
        merchantFetch({
          url: `merchant/banking_application/business/${basBusinessID}?expand_people=true&expand_documents=true`,
        }),
      ];

      Promise.all(bankingApplicationFetchs)
        .then((response) => {
          ICICIStatus.applicationStatus = getICICIApplicationStatus(
            response[0]?.data?.data,
            basBusinessID,
          );
          ICICIStatus.panStatus = getICICIPanStatus(response[1]?.data?.data?.associated_documents);
          setICICIPanStatus(ICICIStatus.panStatus);
          // use applicationStatus and panStatus
          // handle Error Status for RBL
          if (caApplicationBlockedICICIStatus.includes(ICICIStatus.applicationStatus)) {
            const activeStatusOnly =
              getActiveStatusOnly(ICICIStatus.applicationStatus, TRACKER_ERROR, 400) || [];
            setRblActiveStatus([...activeStatusOnly]);
            setRblState([]);
          } else {
            if (
              (ICICIStatus.applicationStatus === ICICIKYCStatus.created ||
                ICICIStatus.applicationStatus === ICICIKYCStatus.user_submitted) &&
              ICICIStatus.panStatus !== PAN_VERIFICATION_STATUSES.verified
            ) {
              let firstTrackerStatus = [];
              // PAN in-progress
              if (ICICIStatus.panStatus === PAN_VERIFICATION_STATUSES.initiated) {
                firstTrackerStatus = getFirstStatus('panInitiated', ICICI_PAN_OR_APP) || [];
              }
              // PAN failed
              else if (
                ICICIStatus.panStatus === PAN_VERIFICATION_STATUSES.failed ||
                ICICIStatus.panStatus === PAN_VERIFICATION_STATUSES.incorrect_details ||
                ICICIStatus.panStatus === PAN_VERIFICATION_STATUSES.not_matched
              ) {
                firstTrackerStatus = getFirstStatus('panFailed', ICICI_PAN_OR_APP) || [];
              } else {
                firstTrackerStatus = getFirstStatus('pending', ICICI_PAN_OR_APP) || [];
              }
              setRblState([...firstTrackerStatus, ...ICICI_STATUS]);
              setRblActiveStatus([...firstTrackerStatus]);
            }
            if (ICICIStatus.applicationStatus === ICICIKYCStatus.user_submitted) {
              if (ICICIStatus.panStatus === PAN_VERIFICATION_STATUSES.verified) {
                const trackerArr = [
                  { ...ICICI_PAN_OR_APP[0], code: 200, highlight: false },
                  ...ICICI_STATUS,
                ];
                const activeStatusOnly = getActiveStatusOnly('recieved', trackerArr) || [];
                setRblState(trackerArr);
                setRblActiveStatus([...activeStatusOnly]);
              }
            } // Explore Current Account CTA
            else if (
              ICICIStatus.applicationStatus === ICICIKYCStatus.account_activated ||
              ICICIStatus.applicationStatus === ICICIKYCStatus.registration_request_sent ||
              ICICIStatus.applicationStatus === ICICIKYCStatus.account_opened
            ) {
              const activeStatusOnly =
                getActiveStatusOnly(ICICIStatus.applicationStatus, ICICI_ACTIVATE) || [];
              setRblActiveStatus([...activeStatusOnly]);
              setRblState([]);
            } else if (
              ICICIStatus.applicationStatus !== ICICIKYCStatus.created &&
              ICICIStatus.applicationStatus !== ICICIKYCStatus.user_submitted
            ) {
              const trackerArr = [
                { ...ICICI_PAN_OR_APP[0], code: 200, highlight: false },
                ...ICICI_STATUS,
              ];
              const viewMoreTrackerList =
                getICICIStatusRenderData(ICICIStatus.applicationStatus, trackerArr) || [];
              const activeStatusOnly =
                getActiveStatusOnly(ICICIStatus.applicationStatus, viewMoreTrackerList) || [];
              setRblState(viewMoreTrackerList);
              setRblActiveStatus([...activeStatusOnly]);
            }
          }
          setBankStatus(ICICIStatus.applicationStatus);
          setShowState('tracker');
        })
        .catch((error) => {
          showNotification({
            type: 'error',
            message: getErrorMessage(error),
            hidePrevious: true,
          });
          setShowState('error');
        });
    }
  }, []);

  switch (showState) {
    case 'loading':
      return <NeoStoneTrackerShimmer />;
    case 'tracker':
      return (
        <div className="nss-tracker" id="nss-tracker">
          <TrackerLeftIllustration proceededBank={proceededBank} />
          <TrackerStatus
            proceededBank={proceededBank}
            bankStatusForCTA={bankStatus}
            viewLessStatus={rblActiveStatus}
            viewMoreStatus={rblState}
            iciciPan={iciciPanStatus}
          />
        </div>
      );
    case 'error':
    default:
      return null;
  }
};

export default compose(
  connect(null, {
    showNotification: showNotificationProp,
  }),
)(NeoStoneTracker);
