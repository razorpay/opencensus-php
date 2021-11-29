import { useState } from 'react';
import { Link } from 'react-router-dom';
import moment from 'moment';
import Amount from 'common/ui/Amount';
import { analyticsTrack } from 'common/utils/analytics';

import { classList, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

function CreditDetails(props) {
  const [showCollapsible, setshowCollapsible] = useState(false);

  const toggleCollapsible = () => {
    if (!showCollapsible) {
      analyticsTrack({
        objectName: props.toggleText,
        actionName: 'viewed',
        screen: 'my account',
        properties: {
          location: 'credits',
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      });
    }
    setshowCollapsible(!showCollapsible);
  };

  const getRemainingPercentage = ({ used, value }) => {
    return Math.round((100 * used) / value);
  };

  const pruneAmountCredits = (items = []) => {
    const expiredCampaigns = [];
    let prunedItems = [];

    // remove expired credits
    prunedItems = items.filter((item) => {
      if (item.campaign.indexOf('Expired') > -1) {
        const campaignName = item.campaign.replace('Expired', '');

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
        item.expired = true;
      }

      return item;
    });
    return prunedItems;
  };

  const { title, description, toggleText } = props;
  const creditItems = pruneAmountCredits(props.creditItems);

  return (
    <div class="balances-container">
      <div class="bal-cont-header">
        <div class="balances-lhs-container">
          <div class="balance-type-container">
            <p>{title}</p>
          </div>
          <div class="balance-amount-container">
            <Amount value={Math.abs(props.totalCredits)} currency="INR" />
          </div>
        </div>
      </div>

      <div class="bal-cont-footer">
        <p>{description}</p>
      </div>

      <div class="coupon-details">
        <div>
          <button
            class="btn-link toggle-history"
            style={!showCollapsible ? { marginBottom: '9px' } : {}}
            onClick={toggleCollapsible}
          >
            <i class={`m-r i-chevron-${!!showCollapsible ? 'up' : 'down'}`} />
            {`${toggleText} (${creditItems.length})`}
          </button>

          {showCollapsible && (
            <div class="collapsible">
              <div class="history">
                {creditItems.map((cItem) => {
                  const isExpired =
                    cItem.expired_at && moment().isAfter(moment(cItem.expired_at, 'X'));
                  return (
                    <div class={classList('container', cItem.expired && 'disabled')} key={cItem.id}>
                      <div class="col-md-6 col-sm-6 col-lg-6 col-xs-12">
                        <div class="row">
                          {cItem.used === cItem.value ? (
                            <>
                              <strong>
                                <Amount value={cItem.value} currency="INR" />
                              </strong>{' '}
                              All credits used
                            </>
                          ) : (
                            <>
                              <strong>
                                <Amount value={cItem.value - cItem.used} currency="INR" />
                              </strong>{' '}
                              of <Amount value={cItem.value} currency="INR" /> is still unused
                            </>
                          )}
                        </div>
                        <div class="row">
                          {`${getRemainingPercentage(cItem)}% consumed`}
                          <br />
                          <span
                            class="progress-bar"
                            style={{
                              width: `${getRemainingPercentage(cItem) * 2}px`,
                            }}
                          />
                          <span class="progress-bar-overlay" />
                        </div>
                      </div>
                      <div class="col-md-6 col-sm-6 col-lg-6 col-xs-12">
                        <div class="row">
                          {isExpired ? (
                            <strong>Expired</strong>
                          ) : cItem.expired_at ? (
                            <>
                              Valid till{' '}
                              <strong>{moment(cItem.expired_at, 'X').format('DD MMM YYYY')}</strong>
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
                  );
                })}
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

export default CreditDetails;
