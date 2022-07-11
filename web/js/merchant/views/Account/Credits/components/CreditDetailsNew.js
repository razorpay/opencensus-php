import { useState } from 'react';
import { Link } from 'react-router-dom';
import { connect } from 'react-redux';
import { compose } from 'redux';
import moment from 'moment';
import Amount from 'common/ui/Amount';

import { analyticsTrack } from 'common/utils/analytics';
import { classList, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import ApplyCouponCodeModal from 'merchant/views/Account/Credits/components/ApplyCouponCodeModal';
import {
  openModal as fnOpenModal,
  closeModal as fnCloseModal,
} from 'merchant_common/reducers/modals';
import rolesList from 'merchant/helpers/permissions/roles-list';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { selfServeTrackInitiate, selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';

function CreditDetails(props) {
  const [showCollapsible, setshowCollapsible] = useState(false);

  const openApplyCouponCodeModal = () => {
    selfServeTrackInitiate({
      selfServeAction: 'Coupon Code Applied',
      page: 'Credits',
      screen: 'My Account',
    });
    analyticsTrack({
      objectName: 'apply coupon code',
      actionName: 'clicked',
      screen: 'my account',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });

    const { openModal, closeModal } = props;
    openModal({
      size: 'small',
      component: <ApplyCouponCodeModal onComplete={closeModal} onClose={closeModal} />,
    });
  };

  const toggleCollapsible = () => {
    if (!showCollapsible) {
      const SS_DATA = {
        selfServeAction: 'Past Coupons Listing Viewed',
        page: 'Credits',
        screen: 'My Account',
      };
      selfServeTrackInitiate(SS_DATA);
      selfServeTrackSuccess(SS_DATA);
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

  const { title, description, toggleText, user, mode } = props;
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
        {['instantly_activated', 'activated', 'activated_mcc_pending'].includes(
          user.activation_status,
        ) && [rolesList.OWNER, rolesList.ADMIN].includes(user.role) ? (
          <div class="balances-add-funds">
            <span>
              <button
                className="btn btn-outline"
                disabled={mode !== 'live'}
                onClick={openApplyCouponCodeModal}
              >
                Apply Coupon Code
              </button>
              {mode !== 'live' ? (
                <Popover align="bottom" theme="dark">
                  <PopoverBody>
                    You cannot apply coupon codes in test mode. Switch to live mode to apply the
                    coupon code.
                  </PopoverBody>
                </Popover>
              ) : null}
            </span>
          </div>
        ) : null}
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

export default compose(
  connect((state) => ({ user: state.session.user, mode: state.session.mode }), {
    openModal: fnOpenModal,
    closeModal: fnCloseModal,
  }),
)(CreditDetails);
