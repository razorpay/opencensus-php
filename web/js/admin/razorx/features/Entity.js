import { razorxFetch } from 'admin/razorx/fetch';

const data = {
  id: 182,
  name: 'Reports-V3-migration',
  description: 'Move reports to ES',
  created_by: 'tom',
  active_experiments: 4,
  created_at: 1546434027,
  updated_at: 1546434027,
  deleted_at: 0,
  variants: ['on', 'off'],
};

export default class extends React.PureComponent {
  state = {};
  componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.fetchFeature(nextProps.id);
    }
  }

  fetchFeature(id) {
    this.setState({
      isFetching: true,
      data: null,
    });

    razorxFetch({ url: '/featureFlags/' + id })
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
    const { id } = this.props;

    let content;

    if (!id) {
      content = null;
    } else if (isFetching) {
      content = <div class="spinner center" />;
    } else if (!isFetching && !data) {
      content = (
        <div class="page-center empty-entity">
          <i class="i-layers" />
          <div class="description">
            <div>ID: {id}</div>
            No Feature found!
          </div>
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
