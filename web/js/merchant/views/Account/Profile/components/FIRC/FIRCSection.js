import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import DetailRow from 'merchant/components/DetailRow';
import Button from 'common/new-ui/Button';
import Popover, { PopoverBody } from 'common/ui/Popover';
import lazy from 'merchant/routes/LazyLoader';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import * as modalActions from 'merchant_common/reducers/modals';
import { fetchPurposeCode } from 'merchant/reducers/profile';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { raiseTicket } from 'merchant/views/TicketSupport/utils';

const FIRCFormModal = lazy(() => import(/* webpackChunkName: "FIRCFormModal" */ './FIRCFormModal'));

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

const SelectPurposeCode = ({ clickHandler }) => (
  <div>
    <a onClick={clickHandler}>Select Code</a>
    <Popover align="top" theme="dark">
      <PopoverBody>
        <>
          <span>To get</span>
          <b>
            <i> purpose code on FIRC</i>
          </b>
          <span>, please update your code here.</span>
        </>
      </PopoverBody>
    </Popover>
  </div>
);

const EditValue = ({ code, description, showPopper = false }) => (
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

    <Button.Transparent onClick={() => raiseTicket()}>
      <i className="i i-edit p-l" />
    </Button.Transparent>
  </div>
);

const DownloadText = ({ clickHandler }) => (
  <a onClick={clickHandler}>
    <span>Download</span>
    <i className="i i-download-blue p-l" />
    <Popover align="top" theme="dark">
      <PopoverBody>
        <span>Download your FIRC from here.</span>
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

  useEffect(() => getFircDetails(), [getFircDetails]);

  const openFircForm = () => {
    props.openModal({
      size: 'medium',
      component: (
        <SuspenseWithLoader>
          <FIRCFormModal />
        </SuspenseWithLoader>
      ),
    });
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

  return (
    <div className="panel panel-default">
      <div className="panel-heading">
        <b>Forward Inwards Remittance Certificate </b>&nbsp;(Proof of Foreign Transfers)
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
                  />
                ) : (
                  <SelectPurposeCode clickHandler={openFircForm} />
                )
              }
            />

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

            <DetailRow
              label="FIRC Certificate"
              value={() => <DownloadText clickHandler={openDownloadFIRCModal} />}
            />
          </div>

          <div className="panel-heading background-highlight">
            Note: FIRC without purpose code could lead to rejections on GST refunds. Update your
            purpose code now!
          </div>
        </>
      )}

      {error && <ErrorText />}
    </div>
  );
};

const mapStateToProps = (state) => ({ firc: state.profile.fircDetails });

const mapDispatchToProps = {
  ...modalActions,
  getFircDetails: fetchPurposeCode,
};

export default connect(mapStateToProps, mapDispatchToProps)(FIRCSection);
