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
import analyticsService from '@commander/services/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

import QueueConfirm from './QueueConfirm';

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
        />
      ))}
    </div>
  );
};

const RewardsListContainer = (props) => {
  return (
    <div class="col-sm-6 RewardsContainer">
      <div class="Rewards--header">
        <h3 class="Rewards--header--title">{props.title}</h3>
        <div class="Rewards--header--desc">{props.subtitle}</div>
      </div>
      <div class="Rewards--list">{props.children}</div>
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
  componentDidMount() {
    this.props.fetchRewards();
  }

  analytics = (action, id) => {
    analyticsService.track({
      objectName: `Reward ${action}`,
      actionName: 'clicked',
      screen: 'Checkout Rewards',
      properties: {
        location: 'rewards',
        rewardId: id,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
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

      if (queuedRewards.length <= 0) {
        if (liveRewards.length >= 3) {
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
        } else if (moment(today).isBefore(start, 'date') && moment(today).isBefore(end, 'date')) {
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
              analyticsService.track({
                objectName: 'Reward',
                actionName: 'moved to queue',
                screen: 'Checkout Rewards',
                properties: {
                  location: 'rewards',
                  rewardId: response.live_reward_id,
                  ...getCommonAnalyticsProperties(window.rzp_user),
                },
              });
              message = 'Reward added into queue successfully.';
            } else if (response.status === 'live') {
              analyticsService.track({
                objectName: 'Reward',
                actionName: 'moved to live',
                screen: 'Checkout Rewards',
                properties: {
                  location: 'rewards',
                  rewardId: response.live_reward_id,
                  ...getCommonAnalyticsProperties(window.rzp_user),
                },
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

  render() {
    let { loading, rewards } = this.props;

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

    return (
      <div class="row">
        <RewardsListContainer
          title="All Rewards from Leading Brands"
          subtitle={
            <>
              <div>Following brands want to promote their offers to your customers.</div>
              <div>Choose offers you want to activate.</div>
            </>
          }
        >
          {availableNowRewards.length > 0 || availableLaterRewards.length > 0 ? (
            <>
              {availableNowRewards.length > 0 && (
                <>
                  <h4 class="h4 Rewards--list-header">Available Now </h4>
                  <RewardsList
                    section={SECTIONS.AVAILABLE}
                    subsection={SUB_SECTIONS.AVAILABLE_NOW}
                    isLoading={loading}
                    rewards={availableNowRewards}
                    editStatus={this.editStatus}
                    checkForQueue={this.checkForQueue}
                    isOneRewardLive={isOneRewardLive}
                  />
                </>
              )}
              {availableLaterRewards.length > 0 && (
                <>
                  <h4 class="h4 Rewards--list-header">Available Later </h4>
                  <RewardsList
                    section={SECTIONS.AVAILABLE}
                    subsection={SUB_SECTIONS.AVAILABLE_LATER}
                    isLoading={loading}
                    rewards={availableLaterRewards}
                    editStatus={this.editStatus}
                    checkForQueue={this.checkForQueue}
                    isOneRewardLive={isOneRewardLive}
                  />
                </>
              )}
            </>
          ) : (
            <div className="Rewards--empty-all-available">
              <img src="/dist/css/assets/rewards_empty.svg" />
              <div className="empty-msg">{getEmptyMessage('allAvailable')}</div>
            </div>
          )}
        </RewardsListContainer>
        <RewardsListContainer
          title="Activated Rewards on Checkout"
          subtitle={
            <>
              <div>Your customers will get below coupons after payment.</div>
              <span
                onClick={() => {
                  analyticsService.track({
                    objectName: 'Preview Checkout',
                    actionName: 'clicked',
                    screen: 'Checkout Rewards',
                    properties: {
                      location: 'rewards',
                      ...getCommonAnalyticsProperties(window.rzp_user),
                    },
                  });
                  return this.props.openModal({
                    size: 'xlarge',
                    component: (
                      <>
                        <div className="preview-img-div">
                          <button type="button" className="close" onClick={this.props.closeModal}>
                            <i class="i i-close" />
                          </button>
                          <img src="/dist/css/assets/rewards_preview_checkout.jpg" />
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
        >
          <h4 class="h4 Rewards--list-header">
            Live Now{' '}
            <small class="help-content">
              <i class="i i-info-outline" />
              <Popover align="bottom" theme="dark" className="reward-live-now-label-popover">
                <PopoverBody>
                  <div>
                    <div>Live rewards appear on your checkout and will</div>
                    <div>be given to your customers after payment. You</div>
                    <div>can run maximum three live rewards at a time.</div>
                  </div>
                </PopoverBody>
              </Popover>
            </small>
          </h4>

          <RewardsList
            section={SECTIONS.LIVE}
            isLoading={loading}
            rewards={liveRewards}
            editStatus={this.editStatus}
            checkForQueue={this.checkForQueue}
            isOneRewardLive={isOneRewardLive}
          />

          {queuedRewards.length > 0 && (
            <>
              <h4 class="h4 Rewards--list-header Rewards--queue-list-header">
                Queue{' '}
                <small class="help-content">
                  <i class="i i-info-outline" />
                  <Popover align="bottom" theme="dark" className="reward-queue-label-popover">
                    <PopoverBody>
                      <div>
                        <div>Rewards in the queue will go-live as per the order</div>
                        <div>when one of the live item expires or you choose to</div>
                        <div>remove it manually.</div>
                      </div>
                    </PopoverBody>
                  </Popover>
                </small>
              </h4>

              <RewardsList
                section={SECTIONS.QUEUE}
                isLoading={loading}
                rewards={queuedRewards}
                editStatus={this.editStatus}
                checkForQueue={this.checkForQueue}
                isOneRewardLive={isOneRewardLive}
              />
            </>
          )}
        </RewardsListContainer>
      </div>
    );
  }
}
