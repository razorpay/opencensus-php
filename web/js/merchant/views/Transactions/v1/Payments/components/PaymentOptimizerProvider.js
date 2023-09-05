import { Link } from 'react-router-dom';
import { connect } from 'react-redux';
import { gatewayLogos } from 'merchant/views/Navigator/components/util';
import { titleCase } from 'common/utils/rzp-utils';

export const findProviderDetails = (terminalProviders, terminal_id, settled_by) => {
  let provider = null;
  if (terminal_id === 'Razorpay') {
    provider = {
      Provider_name: 'Razorpay',
      Gateway: 'razorpay',
    };
  } else if (terminalProviders?.length > 0 && terminal_id) {
    provider = terminalProviders.filter((p) => p.Terminal_id === terminal_id)[0];
  }
  if (!provider && settled_by) {
    provider = {
      Provider_name: titleCase(settled_by),
      Gateway: settled_by === 'Razorpay' ? 'razorpay' : settled_by,
    };
  }
  return provider;
};

const PaymentOptimizerProvider = (props) => {
  const {
    user,
    terminal_id,
    settled_by,
    terminalProviders,
    hideExternalLink,
    isDetailView,
    isTableView,
  } = props;

  const provider = findProviderDetails(terminalProviders, terminal_id, settled_by);
  const hideProviderDetails = provider?.Provider_name === 'Razorpay' ? true : hideExternalLink;

  let providerName = '';
  if (isDetailView && !hideProviderDetails) {
    if (provider?.Provider_name?.length > 23) {
      providerName = `${provider.Provider_name.substr(0, 20)}...`;
    } else if (provider) {
      providerName = provider.Provider_name;
    }
  }

  if (!provider) return <div className="provider-name">--</div>;

  return (
    <>
      <div
        className={`provider-name${isTableView ? ' provider-table-view' : ''}`}
        title={isTableView ? provider.Provider_name : ''}
      >
        <img className="gateway-logo" src={gatewayLogos[provider.Gateway]} alt={provider.Gateway} />
        {isDetailView ? provider.Gateway : provider.Provider_name}
      </div>
      {!hideProviderDetails && (
        <div className="provider-external-link">
          {isDetailView ? <span title={provider.Provider_name}>{`${providerName} `}</span> : ''}
          {user?.hideForNIASupportRole && (
            <Link to={`/optimizer/provider/${terminal_id}`}>
              Provider details
              <i className="i i-external-link" />
            </Link>
          )}
        </div>
      )}
    </>
  );
};

const mapStateToProps = (state) => {
  return {
    user: state.session.user,
  };
};

export default connect(mapStateToProps)(PaymentOptimizerProvider);
