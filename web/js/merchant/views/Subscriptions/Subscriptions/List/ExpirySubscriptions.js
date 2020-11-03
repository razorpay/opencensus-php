import PlaceholderLoader from 'common/ui/PlaceholderLoader';
import Alert from 'common/ui/Forms/Alert';

import { fetchSubscriptionsOverview } from 'merchant/reducers/subscriptions';

const next7Days = moment().add(7, 'days').unix();

const CARDS = [
  {
    title: 'Active subscriptions',
    color: '#2B83EA',
    key: 'subscriptions_active',
    filter: {
      status: 'active',
    },
  },
  {
    title: 'Subscriptions with Failed Payments',
    color: '#D12D2D',
    key: 'subscriptions_failed',
    filter: { status: 'failed' },
  },
  {
    title: `Subscriptions completing in 7 days`,
    color: '#5EBE5B',
    key: 'subscriptions_completing',
    filter: {
      subscriptions_completing: next7Days,
    },
  },
  {
    title: `Subscriptions with Cards Expiring in 7 days`,
    color: '#E38E35',
    key: 'cards_expiring',
    filter: {
      cards_expiring: next7Days,
    },
  },
];

export default class ExpirySubscriptions extends React.Component {
  state = {
    data: {
      subscriptions_active: null,
      subscriptions_failed: null,
      subscriptions_completing: null,
      cards_expiring: null,
    },
    isLoading: true,
    error: null,
  };

  componentDidMount() {
    fetchSubscriptionsOverview(next7Days)
      .then((resp) => {
        this.setState({
          data: resp.data,
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

  render() {
    const { state } = this;

    if (state.error) return <Alert type="error" message={state.error} />;

    return (
      <div class="Subscription--Expiry">
        {CARDS.map((card) => {
          const style = {
            borderLeftColor: card.color,
          };

          return (
            <div class="card" style={style}>
              <div class="count">
                {state.isLoading ? <PlaceholderLoader /> : state.data[card.key]}
              </div>
              <div class="details">{card.title}</div>
            </div>
          );
        })}
      </div>
    );
  }
}
