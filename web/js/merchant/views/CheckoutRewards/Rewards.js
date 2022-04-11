import React, { Component } from 'react';
import Spinner from 'common/ui/Spinner';
import { connect } from 'react-redux';
import moment from 'moment';
import RewardItem, { STATUSES, SECTIONS, SUB_SECTIONS } from './Reward';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { merchantFetch } from 'merchant/utils/ajax';
import * as RewardsListActions from 'merchant/reducers/checkoutRewards';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

import QueueConfirm from './QueueConfirm';

const REWARD_CDN_URL = 'https://cdn.razorpay.com/static/assets/rewards';

const SectionEmptyMsg = {
  allAvailable: {
    title: 'There are no rewards from any brands as of now !!',
    subtitle: 'Please check this space out again later.',
  },
  available: {
    title: 'There are no rewards available now.',
    subtitle: '',
  },
  live: {
    title: 'There are no rewards added to your checkout yet!!',
    subtitle: 'Start adding rewards to your checkout',
  },
  queue: {
    title: 'No rewards in the Queue !',
    subtitle: 'Activate more rewards to create a Queue.',
  },
};

const getEmptyMessage = (section) => {
  return (
    <>
      <div>{SectionEmptyMsg[section].title}</div>
      <div>{SectionEmptyMsg[section].subtitle}</div>
    </>
  );
};

const RewardsList = ({
  rewards,
  isLoading,
  section,
  subsection,
  editStatus,
  checkForQueue,
  isOneRewardLive,
  openModal,
  closeModal,
  isEmailAndContactOptional,
}) => {
  if (isLoading) {
    return (
      <div class="page-spinner-container">
        <Spinner />
      </div>
    );
  }

  if (rewards.length === 0) {
    return <div class="Rewards--empty">{getEmptyMessage(section)}</div>;
  }

  return (
    <div>
      {rewards.map((reward, index) => (
        <RewardItem
          subsection={subsection}
          key={index}
          reward={reward}
          editStatus={editStatus}
          checkForQueue={checkForQueue}
          isOneRewardLive={isOneRewardLive}
          openModal={openModal}
          closeModal={closeModal}
          isEmailAndContactOptional={isEmailAndContactOptional}
        />
      ))}
    </div>
  );
};

const RewardsListContainer = (props) => {
  return (
    <div class="col-sm-6 RewardsContainer">
      <div
        class={`Rewards--header ${
          props.content === 'activated' ? 'Rewards--activated-header' : ''
        }`}
      >
        <h3 class="Rewards--header--title">{props.title}</h3>
        <div class="Rewards--header--desc">{props.subtitle}</div>
      </div>
      <div
        class={`Rewards--list ${props.content === 'activated' ? 'Rewards--activated-list' : ''}`}
      >
        {props.children}
      </div>
    </div>
  );
};

@connect((state) => ({ ...state.rewards, ...state.session }), {
  ...RewardsListActions,
  ...NotificationsActions,
  openModal,
  closeModal,
})
export default class Rewards extends Component {
  constructor() {
    super();
    this.state = {
      isAvailableNowCollapsed: false,
      isAvailableLaterCollapsed: false,
      isLiveCollapsed: false,
      isQueueCollapsed: false,
    };
  }

  componentDidMount() {
    this.props.fetchRewards();
  }

  analytics = (action, id) => {
    analyticsTrack({
      objectName: `Reward ${action}`,
      actionName: 'clicked',
      screen: 'Checkout Rewards',
      properties: {
        location: 'rewards',
        rewardId: id.replace('reward_', ''), // id value looks like "reward_kjJBDkjbmN"
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
      toLumberjack: true,
    });
  };

  checkForQueue = (id, status, starts_at, ends_at) => {
    return new Promise((resolve, reject) => {
      const liveRewards = this.props.rewards.filter((reward) => reward.status === STATUSES.LIVE);
      const queuedRewards = this.props.rewards.filter((reward) => reward.status === STATUSES.QUEUE);
      const today = moment().format('YYYY-MM-DD');
      const start = moment.unix(starts_at).format('YYYY-MM-DD');
      const end = moment.unix(ends_at).format('YYYY-MM-DD');

      const changeStatus = () => {
        this.props.closeModal();
        this.editStatus(id, status)
          .then(() => resolve())
          .catch(() => reject());
      };

      if (
        queuedRewards.length <= 0 &&
        moment(today).isBefore(start, 'date') &&
        moment(today).isBefore(end, 'date')
      ) {
        this.props.openModal({
          size: 'small',
          component: (
            <QueueConfirm
              closeModal={() => {
                reject();
                this.props.closeModal();
              }}
              addToQueue={changeStatus}
            />
          ),
        });
      } else {
        changeStatus();
      }
    });
  };

  editStatus = (id, status) => {
    return new Promise((resolve, reject) => {
      let activate = true;
      if (status == 'live' || status == 'queue') {
        activate = false;
      }
      const updateData = {
        reward_id: id,
        activate,
      };

      if (activate) {
        this.analytics('Activate', id);
      } else {
        this.analytics('Deactivate', id);
      }

      merchantFetch({
        url: `rewards`,
        method: 'patch',
        headers: {
          'content-type': 'application/json',
        },
        data: updateData,
      })
        .then((resp) => {
          if (resp.success) {
            const response = resp.data;

            let message = 'Reward activated successfully.';
            if (response.deactivated_reward_id) {
              message = 'Reward deactivated successfully.';
            }

            if (response.status === 'queue') {
              analyticsTrack({
                objectName: 'Reward',
                actionName: 'moved to queue',
                screen: 'Checkout Rewards',
                properties: {
                  location: 'rewards',
                  rewardId: response.live_reward_id,
                  ...getCommonAnalyticsProperties(window.rzp_user),
                },
                toLumberjack: true,
              });
              message = 'Reward added into queue successfully.';
            } else if (response.status === 'live') {
              analyticsTrack({
                objectName: 'Reward',
                actionName: 'moved to live',
                screen: 'Checkout Rewards',
                properties: {
                  location: 'rewards',
                  rewardId: response.live_reward_id,
                  ...getCommonAnalyticsProperties(window.rzp_user),
                },
                toLumberjack: true,
              });
            }

            this.props.showNotification({
              type: 'success',
              message: message,
            });

            const data =
              this.props.rewards &&
              this.props.rewards.map((item) => {
                const checkId = item.reward_id;
                if (response.live_reward_id && response.live_reward_id === checkId) {
                  item.status = response.status;
                } else if (
                  response.deactivated_reward_id &&
                  response.deactivated_reward_id === checkId
                ) {
                  item.status = response.status;
                }
                return item;
              });

            this.props.activateReward(data);
          }
          resolve();
        })
        .catch(({ errors }) => {
          this.props.showNotification({
            type: 'error',
            message: errors,
          });
          reject();
        });
    });
  };

  /**
   * this function slide up or down the content based on the input subsection
   * @param {string} subsection - this value is for which section we are collapsing
   */
  collapse = (subsection) => {
    let {
      isAvailableNowCollapsed,
      isAvailableLaterCollapsed,
      isLiveCollapsed,
      isQueueCollapsed,
    } = this.state;
    let isSlideUp = true;
    if (subsection === 'available-now') {
      if (isAvailableNowCollapsed) {
        isSlideUp = false;
      }
      isAvailableNowCollapsed = !isAvailableNowCollapsed;
    } else if (subsection === 'available-later') {
      if (isAvailableLaterCollapsed) {
        isSlideUp = false;
      }
      isAvailableLaterCollapsed = !isAvailableLaterCollapsed;
    } else if (subsection === 'live') {
      if (isLiveCollapsed) {
        isSlideUp = false;
      }
      isLiveCollapsed = !isLiveCollapsed;
    } else if (subsection === 'queue') {
      if (isQueueCollapsed) {
        isSlideUp = false;
      }
      isQueueCollapsed = !isQueueCollapsed;
    }

    if (isSlideUp) {
      $(`.Rewards--list--item-${subsection}`).slideUp(); // nosemgrep : javascript.jquery.security.audit.jquery-insecure-selector.jquery-insecure-selector
    } else {
      $(`.Rewards--list--item-${subsection}`).slideDown(); // nosemgrep : javascript.jquery.security.audit.jquery-insecure-selector.jquery-insecure-selector
    }

    this.setState({
      isAvailableNowCollapsed,
      isAvailableLaterCollapsed,
      isLiveCollapsed,
      isQueueCollapsed,
    });
  };

  collapsibleTitle = (subsection, isCollapsed) => {
    return (
      <span className="collapse-action-span" onClick={() => this.collapse(subsection)}>
        {isCollapsed ? 'Show more' : 'Show less'}
        <img
          src={`${REWARD_CDN_URL}/rewards_list_up_vector.svg`}
          className={`arrow-img ${isCollapsed ? 'arrow-img-rotate' : ''}`}
        />
      </span>
    );
  };

  render() {
    const {
      isAvailableNowCollapsed,
      isAvailableLaterCollapsed,
      isLiveCollapsed,
      isQueueCollapsed,
    } = this.state;
    const { loading, rewards, user } = this.props;

    const liveRewards = rewards.filter((reward) => reward.status === STATUSES.LIVE);
    const queuedRewards = rewards.filter((reward) => reward.status === STATUSES.QUEUE);
    const availableRewards = rewards.filter((reward) => reward.status === STATUSES.AVAILABLE);

    const today = moment().format('YYYY-MM-DD');
    const availableNowRewards = availableRewards.filter((reward) => {
      const start = moment.unix(reward.starts_at).format('YYYY-MM-DD');
      const end = moment.unix(reward.ends_at).format('YYYY-MM-DD');
      if (moment(today).isSameOrAfter(start, 'date') && moment(today).isSameOrBefore(end, 'date')) {
        return reward;
      }
    });
    const availableLaterRewards = availableRewards.filter((reward) => {
      const start = moment.unix(reward.starts_at).format('YYYY-MM-DD');
      const end = moment.unix(reward.ends_at).format('YYYY-MM-DD');
      if (moment(today).isBefore(start, 'date') && moment(today).isBefore(end, 'date')) {
        return reward;
      }
    });

    const isOneRewardLive = liveRewards.length > 0 ? true : false;

    const isEmailAndContactOptional =
      user.isPaymentPageEmailOptional && user.isPaymentPageContactOptional;

    return (
      <div class="row">
        <RewardsListContainer
          title="All Rewards from Leading Brands"
          subtitle={
            <>
              <div>Activate as many offers as you want from this list and reward</div>
              <div>your customers for every successful payment on checkout.</div>
            </>
          }
          content="available"
        >
          {availableNowRewards.length > 0 || availableLaterRewards.length > 0 ? (
            <>
              {availableNowRewards.length > 0 && (
                <>
                  <h4 class="h4 Rewards--list-header Rewards--list-sticky-header Rewards--available-now-list-header">
                    Available Now{` (${availableNowRewards.length})`}
                    {this.collapsibleTitle(SUB_SECTIONS.AVAILABLE_NOW, isAvailableNowCollapsed)}
                  </h4>
                  <RewardsList
                    section={SECTIONS.AVAILABLE}
                    subsection={SUB_SECTIONS.AVAILABLE_NOW}
                    isLoading={loading}
                    rewards={availableNowRewards}
                    editStatus={this.editStatus}
                    checkForQueue={this.checkForQueue}
                    isOneRewardLive={isOneRewardLive}
                    openModal={this.props.openModal}
                    closeModal={this.props.closeModal}
                    isEmailAndContactOptional={isEmailAndContactOptional}
                  />
                </>
              )}
              {availableLaterRewards.length > 0 && (
                <>
                  <h4 class="h4 Rewards--list-header">
                    Starting Later{` (${availableLaterRewards.length})`}
                    {this.collapsibleTitle(SUB_SECTIONS.AVAILABLE_LATER, isAvailableLaterCollapsed)}
                  </h4>
                  <RewardsList
                    section={SECTIONS.AVAILABLE}
                    subsection={SUB_SECTIONS.AVAILABLE_LATER}
                    isLoading={loading}
                    rewards={availableLaterRewards}
                    editStatus={this.editStatus}
                    checkForQueue={this.checkForQueue}
                    isOneRewardLive={isOneRewardLive}
                    openModal={this.props.openModal}
                    closeModal={this.props.closeModal}
                    isEmailAndContactOptional={isEmailAndContactOptional}
                  />
                </>
              )}
            </>
          ) : (
            <div className="Rewards--empty-all-available">
              <img src={`${REWARD_CDN_URL}/rewards_empty.svg`} />
              <div className="empty-msg">{getEmptyMessage('allAvailable')}</div>
            </div>
          )}
        </RewardsListContainer>
        {isEmailAndContactOptional ? (
          <div className="col-sm-6 RewardsContainer RewardsContainer--optional-block">
            <h3>Customer e-mail and phone number is required to activate Checkout Rewards</h3>
            <img src={`${REWARD_CDN_URL}/reward_giftbox.svg`} />
            <p>
              Customer e-mail and phone number are both currently optional on your checkout. We
              require those details to share the reward coupon after successful payment from
              Checkout.
            </p>
            <p>In order to make email or phone number mandatory on checkout,</p>
            <p>
              raise a request to <a href="mailto:support@razorpay.com">support@razorpay.com</a>
            </p>
          </div>
        ) : (
          <RewardsListContainer
            title={
              <>
                Activated Rewards on Checkout
                <span
                  onClick={() => {
                    analyticsTrack({
                      objectName: 'Preview Checkout',
                      actionName: 'clicked',
                      screen: 'Checkout Rewards',
                      properties: {
                        location: 'rewards',
                        ...getCommonAnalyticsProperties(window.rzp_user),
                      },
                      toLumberjack: true,
                    });
                    return this.props.openModal({
                      size: 'xlarge',
                      component: (
                        <>
                          <div className="preview-img-div">
                            <button type="button" className="close" onClick={this.props.closeModal}>
                              <i class="i i-close" />
                            </button>
                            <img src={`${REWARD_CDN_URL}/rewards_preview_checkout.jpg`} />
                          </div>
                        </>
                      ),
                      className: 'Rewards--preview-checkout-modal',
                    });
                  }}
                >
                  Preview Checkout
                </span>
              </>
            }
            subtitle={
              <>
                <div>After succesful payment your customers will randomly</div>
                <div>get one of the below coupons that are live now</div>
              </>
            }
            content="activated"
          >
            <h4 class="h4 Rewards--list-header Rewards--list-sticky-header Rewards--live-list-header">
              Live Now{` (${liveRewards.length})`}
              {isOneRewardLive && this.collapsibleTitle(SUB_SECTIONS.LIVE, isLiveCollapsed)}
            </h4>

            <RewardsList
              section={SECTIONS.LIVE}
              subsection={SUB_SECTIONS.LIVE}
              isLoading={loading}
              rewards={liveRewards}
              editStatus={this.editStatus}
              checkForQueue={this.checkForQueue}
              isOneRewardLive={isOneRewardLive}
              openModal={this.props.openModal}
              closeModal={this.props.closeModal}
              isEmailAndContactOptional={isEmailAndContactOptional}
            />

            {queuedRewards.length > 0 && (
              <>
                <h4 class="h4 Rewards--list-header Rewards--queue-list-header">
                  Goes live later{` (${queuedRewards.length}) `}
                  <small class="help-content">
                    <i class="i i-info-outline" />
                    <Popover
                      align="bottom"
                      followPointer={true}
                      theme="dark"
                      className="reward-queue-label-popover"
                    >
                      <PopoverBody>
                        <div>
                          <div>These rewards will get activated on the start date</div>
                        </div>
                      </PopoverBody>
                    </Popover>
                  </small>
                  {this.collapsibleTitle(SUB_SECTIONS.QUEUE, isQueueCollapsed)}
                </h4>

                <RewardsList
                  section={SECTIONS.QUEUE}
                  subsection={SUB_SECTIONS.QUEUE}
                  isLoading={loading}
                  rewards={queuedRewards}
                  editStatus={this.editStatus}
                  checkForQueue={this.checkForQueue}
                  isOneRewardLive={isOneRewardLive}
                  openModal={this.props.openModal}
                  closeModal={this.props.closeModal}
                  isEmailAndContactOptional={isEmailAndContactOptional}
                />
              </>
            )}
          </RewardsListContainer>
        )}
      </div>
    );
  }
}
