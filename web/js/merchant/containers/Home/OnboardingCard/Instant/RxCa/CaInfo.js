import React from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';
import Spinner from 'common/ui/Spinner';
import Primary from './Cards/Primary';
import Secondary from './Cards/Secondary';
import Button from 'common/new-ui/Button';
import { merchantFetch } from 'merchant/utils/ajax';
import { currentAccountStatuses, analyticsStatusMap } from './Cards/data';
import RTracking from 'react-tracking';

const CaInfo = (props) => {
  const [caAccount, setCaAcccount] = React.useState(null);
  const [isloading, setLoading] = React.useState(true);
  const hasAppliedCa = props.user.user.settings['clicked_ca_apply_request_done'];

  const getSFbankAccount = () => {
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
            : analyticsStatusMap['created'],
      }),
    );
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
      .catch((err) => {
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
        url: 'banking_accounts',
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

  if (isloading) {
    return <Spinner className="ca-loader" />;
  }

  const caStatus = caAccount && caAccount.status ? caAccount.status : null;
  const getStatusView = getCaState(caStatus, GoToCaDocs);
  const { pillType, pillText, content, headState } = getStatusView;

  return (
    <>
      <Primary
        settings={props.user.user.settings}
        hasAppliedCa={hasAppliedCa}
        caAccount={caAccount}
        headState={headState}
      />
      <hr className="separator" />
      <Secondary
        hasAppliedCa={hasAppliedCa}
        caAccount={caAccount}
        pillType={pillType}
        pillText={pillText}
        content={content}
      />
    </>
  );
};

const getCaState = (caAccountStatus, GoToCaDocs) => {
  let pillType,
    pillText,
    content,
    headState = '';
  if (!caAccountStatus || caAccountStatus === currentAccountStatuses.created) {
    pillType = 'default';
    pillText = 'Request Received';
    content = (
      <>
        <span>
          Our executive will contact you soon. You can get the application documents ready as per
          your business category.
        </span>{' '}
        <Button.Transparent className="view-doc" onClick={GoToCaDocs}>
          View Documents
        </Button.Transparent>
      </>
    );
  } else if (caAccountStatus === currentAccountStatuses.picked) {
    pillType = 'default';
    pillText = 'Process Started';
    content = (
      <>
        <span>
          RazorpayX has started the application process. You can get the application documents ready
          as per your business category.
        </span>{' '}
        <Button.Transparent className="view-doc" onClick={GoToCaDocs}>
          View Documents
        </Button.Transparent>
      </>
    );
  } else if (caAccountStatus === currentAccountStatuses.processed) {
    pillType = 'yellow';
    pillText = 'Activation In Progress';
    headState = 'Account opened';
    content = <>RazorpayX is working with bank to get your Current Account activated.</>;
  } else if (
    caAccountStatus === currentAccountStatuses.processing ||
    caAccountStatus === currentAccountStatuses.initiated
  ) {
    pillType = 'yellow';
    pillText = 'Bank KYC In Progress';
    headState = 'Documents Recieved';
    content = <>RBL bank has received your form & is working to complete your process.</>;
  } else if (caAccountStatus === currentAccountStatuses.cancelled) {
    pillType = 'danger';
    pillText = 'Request Cancelled';
    content = <>Your current account application has been cancelled.</>;
  } else if (caAccountStatus === currentAccountStatuses.unserviceable) {
    pillType = 'danger';
    pillText = 'Unserviceable';
    content = <>Unfortunately, our banking partner can't service at your location currently. </>;
  } else if (caAccountStatus === currentAccountStatuses.rejected) {
    pillType = 'danger';
    pillText = 'Request Rejected';
    content = (
      <>
        Your current account application has been rejected by our banking partner. We will not be
        able to provide a Current Account at the moment.
      </>
    );
  } else if (caAccountStatus === currentAccountStatuses.activated) {
    pillType = 'success';
    pillText = 'Account Activated';
    content = (
      <>
        Your current account is now active, and you’re ready to take off! You can start exploring
        your account or learn more by reading the guide.{' '}
      </>
    );
  }

  return { pillType, pillText, content, headState };
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});
export default RTracking({ page: 'RXNeoCaHome' })(connect(mapStateToProps, null)(CaInfo));
