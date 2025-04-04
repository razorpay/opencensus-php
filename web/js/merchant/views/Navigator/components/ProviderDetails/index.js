import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import { compose } from 'redux';

import { withRouter } from 'common/deprecated/withRouter';
import Spinner from 'common/ui/Spinner';
import { titleCase, isBlank } from 'common/utils/rzp-utils';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import { gatewayLogos, WalletLabels } from 'merchant/views/Navigator/components/util';
import {
  METHODS_MAP,
  TPV_OPTIONS,
  SEAMLESS_PROVIDERS,
  PROVIDER_KEYS,
  WALLET_AUTO_DEBIT_KEY,
  RAZORPAY_GATEWAY_KEY,
} from 'merchant/views/Navigator/constants';
import { trackOptimizerEvents } from 'merchant/views/Navigator/track';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';

import APIDetails from './components/APIDetails';
import NoProviderFound from './components/NoProviderFound';
import { PAYLATER_LABELS } from '@dashboards/payments/views/Optimizer/AddProvider/constants';

const TPVDetails = ({ tpv }) => {
  if (!isBlank(tpv)) {
    return (
      <div className="list-group details-row-container">
        <EntityDetailRow label="TPV" value={() => TPV_OPTIONS[tpv] || ''} />
      </div>
    );
  }
  return null;
};

class ProviderDetails extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  trackEventOnEdit = () => {
    trackOptimizerEvents({
      objectName: 'edit provider',
      actionName: 'click',
    });
  };

  render() {
    const { providers = {}, user } = this.props;
    const provider = providers.find((item) => item.Terminal_id === this.props.id);

    if (provider) {
      const providerDetails = Object.entries(provider?.Gateway_details || {});

      const wallets = provider.Gateway_details?.wallet_metadata?.wallets || [];
      const walletsNames = wallets.map((wallet) => WalletLabels[wallet] || titleCase(wallet));
      const paylaters = provider.Gateway_details?.paylater_metadata?.paylaters || [];
      const paylatersNames = paylaters.map(
        (paylater) => PAYLATER_LABELS[paylater] || titleCase(paylater),
      );
      const seamlessOptionExist =
        SEAMLESS_PROVIDERS?.includes(provider?.Gateway) &&
        provider?.Gateway_details?.hasOwnProperty('optimizer_seamless_disabled');
      const isRecurringEnabled = provider?.Gateway_details?.hasOwnProperty('Recurring');
      const isRouteEnabled = provider?.Gateway_details?.hasOwnProperty(PROVIDER_KEYS.ROUTE);
      const {
        'UPI Features': upiFeatures,
        'Netbanking Features': netbankingFeatures,
        'Payment Methods': paymentMethods,
        optimizer_seamless_disabled,
        [WALLET_AUTO_DEBIT_KEY]: walletAutoDebit,
        Recurring,
        [PROVIDER_KEYS.ROUTE]: route,
      } = provider?.Gateway_details || {};

      let strPaymentMethods =
        paymentMethods?.map((method) => METHODS_MAP[method])?.join(', ') ?? '';
      const isSodexoEnabled =
        provider?.Gateway_details?.hasOwnProperty(PROVIDER_KEYS.SODEXO) &&
        provider?.Gateway_details?.Sodexo;

      if (isSodexoEnabled) {
        strPaymentMethods += `, ${METHODS_MAP.sodexo}`;
      }

      const isPaytmAutoDebitEnabled =
        provider?.Gateway === 'paytm' && !!user?.isPaytmAutoDebitEnabled;

      const gatewayName =
        provider?.Gateway === RAZORPAY_GATEWAY_KEY ? 'Razorpay' : provider?.Gateway;

      return (
        <div className="content-wrapper content-sm txn-details optimizer-provider-detail">
          {this.props.provider_detail_loading ? (
            <div className="page-spinner-container">
              <Spinner />
            </div>
          ) : (
            <div className="panel panel-default SliderPanel provider-detail-panel">
              <div className="panel-heading">
                <div className="row">
                  <div className="col-xs-7">
                    <b>{provider.Provider_name ? provider.Provider_name : provider.Gateway}</b>
                  </div>
                  <div className="col-xs-5">
                    <Link to={`/optimizer/update-provider/${provider.Terminal_id}`}>
                      <button
                        className="btn btn-primary edit-rule-btn"
                        type="button"
                        onClick={this.trackEventOnEdit}
                      >
                        <i className="i i-pencil-edit" /> Edit Details
                      </button>
                    </Link>
                  </div>
                </div>
              </div>

              <div className="SliderPanel__Body">
                <div className="panel-body">
                  <div className="list-group details-row-container">
                    <EntityDetailRow label="Description" value={() => provider.Description} />
                  </div>
                  <div className="list-group details-row-container">
                    <EntityDetailRow
                      label="Gateway"
                      value={() => (
                        <>
                          <div className="provider-logo-holder">
                            <img
                              src={
                                provider.Gateway_details?.image_url ??
                                gatewayLogos[provider?.Gateway?.toLowerCase()]
                              }
                            />
                          </div>
                          {gatewayName}
                        </>
                      )}
                    />
                  </div>

                  <div className="list-group details-row-container">
                    <EntityDetailRow label="Methods Enabled" value={() => strPaymentMethods} />
                  </div>

                  {walletsNames?.length > 0 && (
                    <div className="list-group details-row-container">
                      <EntityDetailRow
                        label="Wallets Enabled"
                        value={() => walletsNames.join(', ')}
                      />
                    </div>
                  )}
                  {paylatersNames?.length > 0 && (
                    <div className="list-group details-row-container">
                      <EntityDetailRow
                        label="Paylaters Enabled"
                        value={() => paylatersNames.join(', ')}
                      />
                    </div>
                  )}

                  {isPaytmAutoDebitEnabled && (
                    <div className="list-group details-row-container">
                      <EntityDetailRow
                        label="Wallet auto-debit Enabled"
                        value={walletAutoDebit ? 'Yes' : 'No'}
                      />
                    </div>
                  )}

                  {upiFeatures?.tpv ? (
                    <TPVDetails tpv={upiFeatures?.tpv} />
                  ) : netbankingFeatures?.tpv ? (
                    <TPVDetails tpv={netbankingFeatures?.tpv} />
                  ) : null}

                  {seamlessOptionExist && (
                    <div className="list-group details-row-container">
                      <EntityDetailRow
                        label="Integration type"
                        value={optimizer_seamless_disabled ? 'Instant (beta)' : 'Server-to-Server'}
                      />
                    </div>
                  )}

                  {isRecurringEnabled && (
                    <div className="list-group details-row-container">
                      <EntityDetailRow
                        label="Recurring"
                        value={Recurring ? 'Enabled' : 'Disabled'}
                      />
                    </div>
                  )}

                  {isRouteEnabled && (
                    <div className="list-group details-row-container">
                      <EntityDetailRow label="Route" value={route ? 'Enabled' : 'Disabled'} />
                    </div>
                  )}

                  <APIDetails
                    providerDetails={providerDetails}
                    isPaytmAutoDebitEnabled={isPaytmAutoDebitEnabled}
                    walletAutoDebit={walletAutoDebit}
                  />
                </div>
              </div>
            </div>
          )}
        </div>
      );
    }

    return <NoProviderFound />;
  }
}

export default compose(
  connect(
    (state) => {
      return {
        user: state?.session?.user,
        providers: state.navigator.terminalProviders,
      };
    },
    {
      ...ModalActions,
      ...NotificationsActions,
    },
  ),
  withRouter,
)(ProviderDetails);
