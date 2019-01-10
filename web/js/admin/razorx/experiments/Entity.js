import { razorxFetch } from 'admin/razorx/fetch';

const data = {
  id: 201,
  description: 'Random description for this experiment',
  environment: 'testing',
  mode: 'test',
  feature_id: 182,
  segments: [
    {
      variant: 'on',
      type: 'whitelist',
      ids: ['merchant1', 'merchant2'],
      weight: 0,
    },
    {
      variant: 'off',
      type: 'ramp',
      ids: null,
      weight: 8,
    },
  ],
  created_by: 'a@a.com',
  updated_by: '',
  activated_by: 'Quala',
  terminated_by: 'Quala',
  status: 'terminated',
  created_at: 1546434038,
  updated_at: 1546434038,
  activated_at: 1546434062,
  terminated_at: 1546434079,
  deleted_at: 0,
};

export default class extends React.PureComponent {
  state = {};
  componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.fetchExperiment(nextProps.id);
    }
  }

  fetchExperiment(id) {
    this.setState({
      isFetching: true,
      data: null,
    });

    razorxFetch({ url: '/experiments/' + id })
      .then(resp => {
        this.setState({
          isFetching: false,
        });

        if (resp.data) {
          this.setState({
            data: resp.data,
          });
        }
      })
      .catch(({ errors }) => {
        this.setState({
          isFetching: false,
        });
      });
  }

  render() {
    const { isFetching, data } = this.state;
    let content;

    if (!this.props.id) {
      content = null;
    } else if (isFetching) {
      content = <div class="spinner center" />;
    } else if (!isFetching && !data) {
      content = (
        <div>
          <i class="i-flask" />
          {this.props.id}
          <br />
          No Experiment found!
        </div>
      );
    } else {
      content = <Details data={data} />;
    }

    return <div class="entity-container">{content}</div>;
  }
}

const Details = ({ data }) => {
  return <div>Create</div>;
};
