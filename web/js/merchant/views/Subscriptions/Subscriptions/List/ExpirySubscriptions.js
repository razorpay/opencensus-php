import React from 'react';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';
import Alert from 'common/ui/Forms/Alert';
import { fetchSubscriptionsOverview } from 'merchant/reducers/subscriptions';
import { classList, stringifyQueryParams } from 'common/utils/rzp-utils';
import moment from 'moment';
import analytics from '../../analytics';

const next7Days = moment().add(7, 'days').unix();

const CARDS = [
  {
    getTitle: () => (
      <span>
        Active <br /> subscriptions
      </span>
    ),
    color: '#2B83EA',
    key: 'active',
    filter: {
      key: 'status',
      value: 'active',
    },
    eventLabel: 'subscription.filter.active',
  },
  {
    getTitle: () => (
      <span>
        Halted <br /> Subscriptions
      </span>
    ),
    color: '#D12D2D',
    key: 'failed',
    filter: {
      key: 'status',
      value: 'halted',
    },
    eventLabel: 'subscription.filter.halted',
  },
  {
    getTitle: () => (
      <span>
        Subscriptions <br /> completing in 7 days{' '}
      </span>
    ),
    color: '#5EBE5B',
    key: 'complete_before',
    filter: {
      key: 'complete_before',
      value: next7Days,
    },
    eventLabel: 'subscription.filter.comp_7_days',
  },
  {
    getTitle: () => (
      <span>
        Subscriptions with <br /> Cards Expiring in 7 days
      </span>
    ),
    color: '#E38E35',
    key: 'token_expire_before',
    filter: {
      key: 'token_expire_before',
      value: next7Days,
    },
    eventLabel: 'subscription.filter.exp_7_days',
  },
];

export default class ExpirySubscriptions extends React.Component {
  state = {
    selectedQuickFilter: this.props.selectedQuickFilter,
    data: {
      active: null,
      failed: null,
      complete_before: null,
      token_expire_before: null,
    },
    isLoading: true,
    error: null,
  };

  lastAppliedFilter = null;

  componentDidMount() {
    fetchSubscriptionsOverview(next7Days)
      .then(({ data }) => {
        this.setState({
          data: {
            active: data.subscriptions_active,
            failed: data.subscriptions_failed,
            complete_before: data.subscriptions_completing,
            token_expire_before: data.cards_expiring,
          },
          isLoading: false,
        });
      })
      .catch(({ errors }) => {
        let error = (errors || [])[0];
        error = error || 'Something went wrong. Please try again.';

        this.setState({
          isLoading: false,
          error,
        });
      });
  }

  onClickQuickFilter = (card) => () => {
    let selectedQuickFilter;

    if (this.state.selectedQuickFilter) {
      selectedQuickFilter = this.state.selectedQuickFilter !== card.key ? card.key : null;
    } else {
      selectedQuickFilter = card.key;
    }

    this.setState({
      selectedQuickFilter,
    });

    this.props.changeFilterField(this.lastAppliedFilter, null);

    if (!selectedQuickFilter) {
      this.props.history.push({
        pathname: this.props.location.pathname,
        search: stringifyQueryParams({}),
      });

      return;
    }

    const { key, value } = card.filter;
    const filter = {
      [key]: value,
    };
    this.props.history.push({
      pathname: this.props.location.pathname,
      search: stringifyQueryParams(filter),
    });

    this.props.changeFilterField(key, value);

    this.lastAppliedFilter = key;

    analytics.track(card.eventLabel);
  };

  render() {
    const { state } = this;

    if (state.error) return <Alert type="error" message={state.error} />;

    return (
      <div class="Subscription--Expiry">
        {CARDS.map((card) => {
          const style = {
            borderLeftColor: card.color,
          };

          const isActive = state.selectedQuickFilter === card.key;

          return (
            <div
              key={card.key}
              class={classList('card', isActive && 'active')}
              style={style}
              onClick={this.onClickQuickFilter(card)}
            >
              <div class="count">
                {state.isLoading ? <PlaceholderLoader /> : state.data[card.key]}
              </div>
              <div class="details">
                {isActive && <i class="i i-close" />}
                {card.getTitle()}
              </div>
            </div>
          );
        })}
      </div>
    );
  }
}
