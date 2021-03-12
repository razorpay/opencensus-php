import { Component } from 'react';
import { Link } from 'react-router-dom';
import moment from 'moment';
import Amount from 'common/ui/Amount';
import { analyticsTrack } from 'common/utils/analytics';

import { classList, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

export default class CreditDetails extends Component {
  state = {
    showCollapsible: false,
  };

  toggleCollapsible = () => {
    const showCollapsible = this.state.showCollapsible;
    if (!showCollapsible) {
      analyticsTrack({
        objectName: this.props.toggleText,
        actionName: 'viewed',
        screen: 'my account',
        properties: {
          location: 'credits',
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      });
    }

    this.setState(
      {
        showCollapsible: !showCollapsible,
      },
      () => {
        this.props.trackToggleHistory(this.props.title)(!showCollapsible);
      },
    );
  };

  getRemainingPercentage = ({ used, value }) => {
    return Math.round((100 * used) / value);
  };

  pruneAmountCredits = (items = []) => {
    let expiredCampaigns = [],
      prunedItems = [];

    // remove expired credits
    prunedItems = items.filter((item) => {
      if (item.campaign.indexOf('Expired') > -1) {
        let campaignName = item.campaign.replace('Expired', '');

        expiredCampaigns.push(campaignName);
        return false;
      }

      if (item.used === item.value) {
        expiredCampaigns.push(item.campaign);
      }
      return true;
    });

    // add expired property if all credits used
    prunedItems = prunedItems.map((item) => {
      if (expiredCampaigns.indexOf(item.campaign) > -1 || item.used === item.value) {
        item['expired'] = true;
      }

      return item;
    });
    return prunedItems;
  };

  render() {
    const { title, totalCredits, description, toggleText, onManageAlert } = this.props;
    const { showCollapsible } = this.state;

    const creditItems = this.pruneAmountCredits(this.props.creditItems);

    return (
      <div class="panel panel-default coupon-details">
        <div class="panel-body">
          {onManageAlert && (
            <button class="btn btn-default pull-right" onClick={onManageAlert}>
              Manage Alerts
            </button>
          )}

          <strong class="big-font">{title}</strong>

          <p class="puck">{description}</p>

          <small>Total: </small>
          <strong class="big-font">
            <Amount value={totalCredits} currency={'INR'} />
          </strong>
          <div class="collapsible-container">
            {!!creditItems.length && (
              <button class="btn-link toggle-history" onClick={this.toggleCollapsible}>
                <i class={`m-r i-chevron-${!!showCollapsible ? 'up' : 'down'}`} />
                {`${toggleText} (${creditItems.length})`}
              </button>
            )}
            {showCollapsible && (
              <div class="collapsible">
                <div class="history">
                  {!!creditItems.length &&
                    creditItems.map((cItem) => (
                      <div
                        class={classList('container', cItem.expired && 'disabled')}
                        key={cItem.id}
                      >
                        <div class="col-md-6 col-sm-6 col-lg-6 col-xs-12">
                          <div class="row">
                            {cItem.used === cItem.value ? (
                              <>
                                <strong>
                                  <Amount value={cItem.value} currency={'INR'} />
                                </strong>{' '}
                                All credits used
                              </>
                            ) : (
                              <>
                                <strong>
                                  <Amount value={cItem.value - cItem.used} currency={'INR'} />
                                </strong>{' '}
                                of <Amount value={cItem.value} currency={'INR'} /> is still unused
                              </>
                            )}
                          </div>
                          <div class="row">
                            {`${this.getRemainingPercentage(cItem)}% consumed`}
                            <br />
                            <span
                              class="progress-bar"
                              style={{
                                width: `${this.getRemainingPercentage(cItem) * 2}px`,
                              }}
                            />
                            <span class="progress-bar-overlay" />
                          </div>
                        </div>
                        <div class="col-md-6 col-sm-6 col-lg-6 col-xs-12">
                          <div class="row">
                            {cItem.expired_at ? (
                              <>
                                Valid till{' '}
                                <strong>
                                  {moment(cItem.expired_at, 'X').format('DD MMM YYYY')}
                                </strong>
                              </>
                            ) : (
                              'Unlimited Validity'
                            )}
                          </div>
                          <div class="row">
                            <strong>{cItem.campaign}</strong> coupon applied
                            <Link to={`/credits/${cItem.id}`} class="m-l">
                              View Details
                            </Link>
                          </div>
                        </div>
                      </div>
                    ))}
                </div>
              </div>
            )}
          </div>
        </div>
      </div>
    );
  }
}
