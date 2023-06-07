import React, { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter, Link } from 'react-router-dom';
import PropTypes from 'prop-types';

import Spinner from 'common/ui/Spinner';
import { titleCase, isBlank } from 'common/utils/rzp-utils';

import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import EntityDetailRow from 'merchant/components/EntityDetailRow';

import { trackOptimizerEvents } from 'merchant/views/Navigator/track';
import { gatewayLogos, WalletLabels } from 'merchant/views/Navigator/components/util';
import {
  TPV_OPTIONS,
  SEAMLESS_PROVIDERS,
  PROVIDER_KEYS,
  WALLET_AUTO_DEBIT_KEY,
} from 'merchant/views/Navigator/constants';

import APIDetails from './components/APIDetails';
import NoProviderFound from './components/NoProviderFound';

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

@withRouter
@connect(
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
)
export default class ProviderDetails extends Component {
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
      const seamlessOptionExist =
        SEAMLESS_PROVIDERS?.includes(provider?.Gateway) &&
        provider?.Gateway_details?.hasOwnProperty('optimizer_seamless_disabled');
      const {
        'UPI Features': upiFeatures,
        'Netbanking Features': netbankingFeatures,
        'Payment Methods': paymentMethods,
        optimizer_seamless_disabled,
        [WALLET_AUTO_DEBIT_KEY]: walletAutoDebit,
      } = provider?.Gateway_details || {};

      let strPaymentMethods = paymentMethods?.join(', ') ?? '';
      const isSodexoEnabled =
        provider?.Gateway_details?.hasOwnProperty(PROVIDER_KEYS.SODEXO) &&
        provider?.Gateway_details?.Sodexo;

      if (isSodexoEnabled) {
        strPaymentMethods += ', sodexo';
      }

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
                            <img src={gatewayLogos[provider.Gateway.toLowerCase()]} />
                          </div>
                          {provider?.Gateway}
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

                  {provider?.Gateway === 'paytm' && user?.isPaytmAutoDebitEnabled && (
                    <div className="list-group details-row-container">
                      <EntityDetailRow
                        label="Wallet auto-debit Enabled"
                        value={walletAutoDebit ? 'Yes' : 'No'}
                      />
                    </div>
                  )}

                  <TPVDetails tpv={upiFeatures?.tpv} />

                  <TPVDetails tpv={netbankingFeatures?.tpv} />

                  {seamlessOptionExist && (
                    <div className="list-group details-row-container">
                      <EntityDetailRow
                        label="Integration type"
                        value={optimizer_seamless_disabled ? 'Instant (beta)' : 'Server-to-Server'}
                      />
                    </div>
                  )}

                  <APIDetails providerDetails={providerDetails} />
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
