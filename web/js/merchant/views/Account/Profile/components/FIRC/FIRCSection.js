import React, { useEffect } from 'react';
import { connect } from 'react-redux';

import Button from 'common/new-ui/Button';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import DetailRow from 'merchant/components/DetailRow';
import ShowWhen from 'merchant/components/ShowWhen';
import { fetchPurposeCode } from 'merchant/reducers/profile';
import lazy from 'merchant/routes/LazyLoader';
import { trackPurposeCodePopupOpened } from 'merchant/views/Account/Profile/components/FIRC/analytics';
import { raiseTicket } from 'merchant/views/TicketSupport/utils';
import * as modalActions from 'merchant_common/reducers/modals';

const FIRCFormModal = lazy(() => import(/* webpackChunkName: "FIRCFormModal" */ './FIRCFormModal'));
const HSCodeModal = lazy(() =>
  import(/* webpackChunkName: "HSCodeModal" */ './HSCode/HSCodeModal'),
);
const HSCodeDetail = lazy(() => import(/* webpackChunkName: "HSCodeDetail" */ './HSCode'));

const DownloadFIRCForm = lazy(() =>
  import(/* webpackChunkName: "DownloadFIRCForm" */ './DownloadFIRCForm'),
);

const Spinner = ({ loading }) => (
  <div className="pull-right">
    <span className={`spin-btn medium ${loading ? 'visible' : 'hidden'}`} />
  </div>
);

const Label = ({ title, description }) => (
  <div>
    <span className="cursor-pointer">{title}</span>
    <small className="help-content">
      <i className="i i-info-outline" />
      <Popover align="top" theme="dark">
        <PopoverBody>
          <span>{description}</span>
        </PopoverBody>
      </Popover>
    </small>
  </div>
);

const SelectPurposeCode = ({ clickHandler }) => {
  const onClick = () => clickHandler();
  return (
    <div>
      <a onClick={onClick}>Select Code</a>
      <Popover align="top" theme="dark">
        <PopoverBody>
          <>
            <span>To get</span>
            <b>
              <i> purpose code on FIRS</i>
            </b>
            <span>, please update your code here.</span>
          </>
        </PopoverBody>
      </Popover>
    </div>
  );
};

const SelectHSCode = ({ clickHandler }) => (
  <SuspenseWithLoader>
    <HSCodeDetail onClick={clickHandler} />
  </SuspenseWithLoader>
);

const EditValue = ({ code, description, showPopper = false, onEditPurposeCode }) => {
  const handleEditClick = () => {
    if (typeof onEditPurposeCode === 'function') {
      onEditPurposeCode(code);
    } else {
      raiseTicket();
    }
  };

  return (
    <div>
      <span>
        {code}
        {showPopper && (
          <Popover align="top" theme="dark">
            <PopoverBody>
              <b>{code}</b> - {description}
            </PopoverBody>
          </Popover>
        )}
      </span>

      <Button.Transparent onClick={handleEditClick}>
        <i className="i i-edit p-l" />
      </Button.Transparent>
    </div>
  );
};

const DownloadText = ({ clickHandler }) => (
  <a onClick={clickHandler}>
    <span>Download</span>
    <i className="i i-download-blue p-l" />
    <Popover align="top" theme="dark">
      <PopoverBody>
        <span>Download your FIRS from here.</span>
      </PopoverBody>
    </Popover>
  </a>
);

const ErrorText = () => (
  <div className="panel-heading background-highlight">Fetching Purpose Code failed!</div>
);

const FIRCSection = (props) => {
  const { firc, getFircDetails } = props;
  const { loading, data, error } = firc;

  const trackModalEvent = (action) => {
    analyticsTrack({
      objectName: 'FIRC Modal',
      actionName: action,
      screen: 'profile',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
      toLumberjack: true,
    });
  };

  const openFircForm = (code) => {
    props.openModal({
      size: 'medium',
      component: (
        <SuspenseWithLoader>
          <FIRCFormModal editMode={Boolean(code)} code={code} />
        </SuspenseWithLoader>
      ),
    });

    trackPurposeCodePopupOpened();
  };

  const openDownloadFIRCModal = () => {
    props.openModal({
      size: 'small',
      component: (
        <SuspenseWithLoader>
          <DownloadFIRCForm user={props.user} />
        </SuspenseWithLoader>
      ),
    });
    trackModalEvent('opened');
  };

  const openHSCodeModal = () => {
    props.openModal({
      size: 'medium',
      component: (
        <SuspenseWithLoader>
          <HSCodeModal />
        </SuspenseWithLoader>
      ),
    });
  };

  useEffect(() => getFircDetails(), [getFircDetails]);

  useEffect(() => {
    !error && !loading && trackModalEvent('loading finished');
  }, [error, loading]);

  return (
    <div className="panel panel-default">
      <div className="panel-heading">
        <b>International Payments Codes</b>
        <Spinner loading={loading} />
      </div>

      {!loading && !error && (
        <>
          <div className="list-group details-row-container">
            <DetailRow
              label={() => (
                <Label
                  title="Purpose Code"
                  description="Purpose Code is a code issued by Reserve Bank of India (RBI) to classify the nature of inward foreign currency transaction."
                />
              )}
              value={() =>
                data?.purpose_code ? (
                  <EditValue
                    code={data.purpose_code}
                    description={data.purpose_code_desc}
                    showPopper={true}
                    onEditPurposeCode={openFircForm}
                  />
                ) : (
                  <SelectPurposeCode clickHandler={openFircForm} />
                )
              }
            />
            <ShowWhen featureEnabled="opgsp_import_flow">
              <DetailRow
                label={() => (
                  <Label
                    title="Harmonized System Code"
                    description="The Harmonized System (HS) code is a standard classification for classifying the globally traded products by the custom authorities."
                  />
                )}
                value={() => <SelectHSCode clickHandler={openHSCodeModal} />}
              />
            </ShowWhen>

            {data?.iec_code && (
              <DetailRow
                label={() => (
                  <Label
                    title="IEC Code"
                    description="Import Export Code is a code issued by Directorate General of Foreign Trade (DGFT)."
                  />
                )}
                value={() => <EditValue code={data.iec_code} />}
              />
            )}

            <ShowWhen
              additionalCondition={(user) => user.isAccountAndSettingsRevampEnabled !== true}
            >
              <DetailRow
                label="FIRS Certificate"
                value={() => <DownloadText clickHandler={openDownloadFIRCModal} />}
              />
            </ShowWhen>
          </div>

          <div className="panel-heading background-highlight">
            Note: FIRS without purpose code could lead to rejections on GST refunds. Update your
            purpose code now!
          </div>
        </>
      )}

      {error && <ErrorText />}
    </div>
  );
};

const mapStateToProps = (state) => ({ firc: state.profile.fircDetails, user: state.session.user });

const mapDispatchToProps = {
  ...modalActions,
  getFircDetails: fetchPurposeCode,
};

export default connect(mapStateToProps, mapDispatchToProps)(FIRCSection);
