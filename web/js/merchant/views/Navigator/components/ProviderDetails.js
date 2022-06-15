import Spinner from 'common/ui/Spinner';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import React, { Component, Fragment } from 'react';
import { connect } from 'react-redux';
import { withRouter, Link } from 'react-router-dom';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import { trackOptimizerEvents } from 'merchant/views/Navigator/track';
import PropTypes from 'prop-types';
import { titleCase } from 'common/utils/rzp-utils';
import { gatewayLogos } from './util';

@withRouter
@connect(
  (state) => {
    return {
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
    const { providers } = this.props;
    const provider = providers.filter((item) => item.Terminal_id === this.props.id)[0];
    if (provider) {
      const detailsKeys = Object.keys(provider.Gateway_details);
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
                        <i className="i i-pencil-edit" />
                        Edit Details
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
                    <EntityDetailRow
                      label="Methods Enabled"
                      value={() => provider.Gateway_details['Payment Methods'].join(', ')}
                    />
                  </div>
                  <div className="list-group details-row-container">
                    <EntityDetailRow
                      label="Production API Details"
                      value={() => (
                        <div className="provider-api-details">
                          {detailsKeys.map((key, index) => {
                            if (key != 'Payment Methods' && !key.includes('metadata')) {
                              return (
                                <Fragment key={index}>
                                  <div className="key-name">{titleCase(key)}</div>
                                  <div className="key-value">
                                    {provider.Gateway_details[key]
                                      ? provider.Gateway_details[key]
                                      : '**********'}
                                  </div>
                                </Fragment>
                              );
                            } else {
                              return null;
                            }
                          })}
                        </div>
                      )}
                    />
                  </div>
                </div>
              </div>
            </div>
          )}
        </div>
      );
    }
    return (
      <div className="content-wrapper content-sm txn-details optimizer-provider-detail">
        <div className="panel panel-default SliderPanel provider-detail-panel">
          <div className="panel-heading">
            <div className="row">
              <div className="col-xs-7">
                <b>Provider</b>
              </div>
              <div className="col-xs-5" />
            </div>
          </div>
          <div className="SliderPanel__Body">
            <div className="panel-body">
              <p className="no-provider">No target provider found!</p>
            </div>
          </div>
        </div>
      </div>
    );
  }
}
