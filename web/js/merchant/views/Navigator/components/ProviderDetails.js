import Spinner from 'common/ui/Spinner';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import React, { Component, Fragment } from 'react';
import { connect } from 'react-redux';
import { withRouter, Link } from 'react-router-dom';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';

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

  render() {
    const { providers } = this.props;
    const provider = providers.filter((item) => item.Terminal_id === this.props.id)[0];
    const detailsKeys = Object.keys(provider.Gateway_details);
    return (
      <div class="content-wrapper content-sm txn-details optimizer-provider-detail">
        {this.props.provider_detail_loading ? (
          <div class="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          <div class="panel panel-default SliderPanel provider-detail-panel">
            <div class="panel-heading">
              <div className="row">
                <div className="col-xs-7">
                  <b>{provider.Provider_name ? provider.Provider_name : provider.Gateway}</b>
                </div>
                <div className="col-xs-5">
                  <Link to={`/optimizer/update-provider/${provider.Terminal_id}`}>
                    <button className="btn btn-primary edit-rule-btn">
                      {' '}
                      <i className="i i-pencil-edit" />
                      Edit Details
                    </button>
                  </Link>
                </div>
              </div>
            </div>

            <div class="SliderPanel__Body">
              <div class="panel-body">
                <div class="list-group details-row-container">
                  <EntityDetailRow label="Description" value={() => provider.Description} />
                </div>
                <div class="list-group details-row-container">
                  <EntityDetailRow
                    label="Gateway"
                    value={() => (
                      <>
                        <div class="provider-logo-holder">
                          <img src={gatewayLogos[provider.Gateway.toLowerCase()]} />
                        </div>
                        {provider.Gateway}
                      </>
                    )}
                  />
                </div>
                <div class="list-group details-row-container">
                  <EntityDetailRow
                    label="Methods Enabled"
                    value={() => provider.Gateway_details['Payment Methods'].join(', ')}
                  />
                </div>
                <div class="list-group details-row-container">
                  <EntityDetailRow
                    label="Production API Details"
                    value={() => (
                      <div className="provider-api-details">
                        {detailsKeys.map((key, index) => {
                          if (key != 'Payment Methods') {
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
}
