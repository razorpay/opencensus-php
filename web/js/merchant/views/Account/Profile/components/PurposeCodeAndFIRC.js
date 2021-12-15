import { connect } from 'react-redux';
import { useState, useEffect } from 'react';

import Popover, { PopoverBody } from 'common/ui/Popover';
import * as modalActions from 'merchant_common/reducers/modals';
import { fetchPurposeCode } from 'merchant/reducers/profile';
import { showNotification as fnNotification } from 'merchant_common/reducers/notifications';
import lazy from 'merchant/routes/LazyLoader';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

const SelectPurposeCodeForm = lazy(() =>
  import(
    /* webpackChunkName: "SelectPurposeCodeForm" */ 'merchant/views/Account/Profile/components/SelectPurposeCodeForm'
  ),
);

const DownloadFIRCForm = lazy(() =>
  import(
    /* webpackChunkName: "DownloadFIRCForm" */ 'merchant/views/Account/Profile/components/FIRC/DownloadFIRCForm'
  ),
);

const PurposeCodeAndFIRC = (props) => {
  const [purposeCode, setPurposeCode] = useState(null);
  const [codeDescription, setCodeDescription] = useState(null);
  const [isLoading, setIsLoading] = useState(true);

  const { user, showNotification } = props;

  const setPurposeCodeAndDesc = (code, desc) => {
    setPurposeCode(code);
    setCodeDescription(desc);
  };

  const openDownloadFIRCModal = () => {
    props.openModal({
      size: 'small',
      component: (
        <SuspenseWithLoader>
          <DownloadFIRCForm user={props.user} />,
        </SuspenseWithLoader>
      ),
    });

    analyticsTrack({
      objectName: 'FIRC Modal',
      actionName: 'opened',
      screen: 'profile',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
      toLumberjack: true,
    });
  };

  useEffect(() => {
    fetchPurposeCode(user.email)
      .then((res) => {
        if (res?.data?.merchants) {
          const merchants = res.data.merchants;
          if (merchants.length > 0) {
            const merchantData = merchants[0];
            const fetchedPurposeCode = merchantData.purpose_code;
            const fetchedPurposeCodeDescription = merchantData.purpose_code_desc;
            setPurposeCode(fetchedPurposeCode);
            setCodeDescription(fetchedPurposeCodeDescription);
            setIsLoading(false);
          }
        }
      })
      .catch(() => {
        showNotification({
          type: 'error',
          message: 'Sorry! Could not fetch purpose code details',
        });
        setIsLoading(false);
      });
  }, [showNotification, user.email]);

  const openSelectPurposeCodeModal = () => {
    props.openModal({
      size: 'medium',
      component: (
        <SuspenseWithLoader>
          <SelectPurposeCodeForm onSetPurposeCode={setPurposeCodeAndDesc} />
        </SuspenseWithLoader>
      ),
    });
  };

  return (
    <div className="panel panel-default">
      <div className="panel-heading">
        <b>Forward Inwards Remittance Certificate </b>&nbsp; &nbsp; (Proof of Foreign Transfers)
      </div>
      <div className="list-group details-row-container">
        <div className="list-group-item">
          <span>
            <span>Purpose Code</span>
            <small className="help-content">
              <i className="i i-info-outline" />
              <Popover align="bottom" theme="dark">
                <PopoverBody>
                  <span>
                    Purpose Code is a code issued by Reserve Bank of India (RBI) to classify the
                    nature of inward foreign currency transaction.
                  </span>
                </PopoverBody>
              </Popover>
            </small>
          </span>
          <div>
            {isLoading ? (
              <span>Loading...</span>
            ) : purposeCode ? (
              <span>
                <b>
                  <a>{purposeCode}</a>
                </b>
                <Popover align="bottom" theme="dark">
                  <PopoverBody>
                    <div>
                      <b>{purposeCode}</b> - {codeDescription}
                    </div>
                  </PopoverBody>
                </Popover>
                <i
                  className="i i-edit_board purpose-code-edit-button"
                  onClick={props.onEditClick}
                />
              </span>
            ) : (
              <>
                <a onClick={openSelectPurposeCodeModal}>Select Code</a>
                <Popover align="bottom" theme="dark">
                  <PopoverBody>
                    <>
                      <span>To get</span>
                      <b>
                        <i> purpose code on FIRC</i>
                      </b>
                      <span> , please update your code here.</span>
                    </>
                  </PopoverBody>
                </Popover>
              </>
            )}
          </div>
        </div>
        <div className="list-group-item">
          <span>FIRC Certificate</span>
          <div className="firc-box">
            <span>
              <a onClick={openDownloadFIRCModal}>Download</a>
              <i className="i i-download-blue" onClick={openDownloadFIRCModal} />
              <Popover align="bottom" theme="dark">
                <PopoverBody>
                  <div>Download your FIRC from here</div>
                </PopoverBody>
              </Popover>
            </span>
          </div>
        </div>
      </div>
      <div className="panel-heading background-highlight">
        Note: FIRC without purpose code could lead to rejections on GST refunds. Update your purpose
        code now!
      </div>
    </div>
  );
};

export default connect(null, { ...modalActions, showNotification: fnNotification })(
  PurposeCodeAndFIRC,
);
