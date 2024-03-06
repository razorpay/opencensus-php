import React from 'react';
import { connect } from 'react-redux';
import rTracking from 'react-tracking';
import { bindActionCreators } from 'redux';

import Spinner from 'common/ui/Spinner';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import { merchantFetch } from 'merchant/utils/ajax';
import { updateUser } from 'merchant_common/reducers/user';

import Primary from './Cards/Primary';
import Secondary from './Cards/Secondary';
import { currentAccountStatuses, analyticsStatusMap } from './Cards/data';
import Faq from './Faq';
import { getCaState } from './data';
import { getTimeDiff } from './helpers';

const CaInfo = (props) => {
  const [caAccount, setCaAcccount] = React.useState(null);
  const [isloading, setLoading] = React.useState(true);
  const hasAppliedCa = props?.user?.user?.settings?.clicked_ca_apply_request_done;
  const hideTimeline = !!props?.user?.user?.settings?.clicked_close_ca_timeline_banner;
  const { showNitroRXCAFlow } = props;

  const getSFbankAccount = (data) => {
    const lossReason = data[0].opportunityLossReason || '';
    let status;
    switch (lossReason) {
      case 'Merchant Cancelled':
        status = 'cancelled';
        break;

      case 'RZP Rejected':
        status = 'rejected';
        break;

      default:
        status = 'created';
        break;
    }
    const account = {
      id: 'test_id',
      account_type: 'current',
      status,
      channel: 'rbl',
    };
    return account;
  };

  const sendViewEvent = (status) => {
    props.tracking.trackEvent(
      window.rzpQ.merchantActions().viewed('dashboard.neopricing_tracker', {
        status: status ? analyticsStatusMap[status] : 'application_pending',
      }),
    );
    props.updateCAstatus(status);
  };

  const GoToCaDocs = () => {
    window.open('https://razorpay.com/docs/razorpayx/current-account/', '_blank');
    props.tracking.trackEvent(
      window.rzpQ.merchantActions().clicked('dashboard.neopricing_tracker', {
        clicked_on: 'view_documents',
        status:
          caAccount && caAccount.status
            ? analyticsStatusMap[caAccount.status]
            : analyticsStatusMap.created,
      }),
    );
  };

  const handleAnnouncementClose = () => {
    const _settings = { ...props.user.user.settings };
    _settings.clicked_close_ca_timeline_banner = '1';
    merchantFetch({
      url: 'users',
      mode: 'live',
      method: 'patch',
      data: { settings: _settings },
    }).then(() => {
      updateUser({ settings: _settings });
    });
  };

  const getDataFromSF = () => {
    const { id } = props.user.merchant;
    merchantFetch({
      url: `merchant/${id}/salesforce_opportunity_detail?opportunity[]=Current_Account`,
      mode: 'live',
      method: 'get',
    })
      .then(({ data }) => {
        if (data && data.length > 0) {
          const account = getSFbankAccount(data);
          setCaAcccount(account);
          const { status } = account;
          sendViewEvent(status);
        } else {
          sendViewEvent('created');
        }
      })
      .catch(() => {
        sendViewEvent('created');
      })
      .finally(() => {
        setLoading(false);
      });
  };

  React.useEffect(() => {
    setLoading(true);
    if (hasAppliedCa) {
      merchantFetch({
        url: 'banking_accounts?fee_recovery=0',
        mode: 'live',
        method: 'get',
      }).then(({ data }) => {
        let hasCa = false;
        data.items.forEach((account) => {
          if (account.account_type === 'current') {
            setCaAcccount(account);
            sendViewEvent(account.status);
            hasCa = true;
          }
        });
        if (!hasCa) {
          getDataFromSF();
        } else {
          setLoading(false);
        }
      });
    } else {
      sendViewEvent(null);
      setLoading(false);
    }
  }, []);

  if (hideTimeline) return null;

  if (isloading) {
    return <Spinner className="ca-loader" />;
  }

  const caStatus = caAccount && caAccount.status ? caAccount.status : null;
  const caSubStatus = caAccount?.sub_status || null;
  const activatedAt =
    props.user.user.merchants && props.user.user.merchants.length
      ? props.user.merchant.activated_at
      : null;

  if (
    caStatus &&
    caStatus === currentAccountStatuses.activated &&
    getTimeDiff(caAccount.status_last_updated_at, 7) < 0
  ) {
    return null;
  } else if (
    ((!hasAppliedCa && getTimeDiff(activatedAt, 91) < 0) ||
      (caStatus !== currentAccountStatuses.activated && getTimeDiff(activatedAt, 91) < 0)) &&
    !showNitroRXCAFlow
  ) {
    return (
      <AnnouncementBanner
        title="Pricing has been reverted"
        theme="danger"
        canBeClosed={true}
        handleClose={handleAnnouncementClose}
        card_id="current-account-pricing-reverted-banner"
      >
        Your have failed to open a RazorpayX current account due to which you have been reverted to
        classic pricing with 2% transaction rate
      </AnnouncementBanner>
    );
  }

  const getStatusView = getCaState(caStatus, caSubStatus, GoToCaDocs, showNitroRXCAFlow);
  const { pillType, pillText, content, headState, title, viewType } = getStatusView;

  return (
    <>
      {viewType === 'announcement' ? (
        <AnnouncementBanner
          title={title}
          theme="danger"
          canBeClosed={true}
          handleClose={handleAnnouncementClose}
        >
          {content}
        </AnnouncementBanner>
      ) : (
        <div className="rx-ca-home-container">
          <div className="ca-container">
            <Primary
              settings={props.user.user.settings}
              hasAppliedCa={hasAppliedCa}
              caAccount={caAccount}
              headState={headState}
              caStatus={caStatus}
              activatedAt={activatedAt}
              pillType={pillType}
              pillText={pillText}
              showNitroRXCAFlow={showNitroRXCAFlow}
            />
            <hr className="separator" />
            <Secondary
              hasAppliedCa={hasAppliedCa}
              caAccount={caAccount}
              pillType={pillType}
              pillText={pillText}
              content={content}
              showNitroRXCAFlow={showNitroRXCAFlow}
            />
          </div>
          <div className="faq-container">
            <Faq caStatus={caStatus} showNitroRXCAFlow={showNitroRXCAFlow} />
          </div>
        </div>
      )}
    </>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

const mapDispatchToProps = (dispatch) => ({
  updateUser: bindActionCreators(updateUser, dispatch),
});

export default rTracking({ page: 'RXNeoCaHome' })(
  connect(mapStateToProps, mapDispatchToProps)(CaInfo),
);
