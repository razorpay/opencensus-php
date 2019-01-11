import { Link } from 'react-router-dom';
import { formatDate, titleCase } from 'common/util';
import { razorxFetch } from 'admin/razorx/fetch';

const dummy_data = {
  id: 182,
  name: 'Reports-V3-migration',
  description:
    'Move reports to ES Move reports to ES Move reports to ES Move reports to ES',
  created_by: 'tom',
  active_experiments: 4,
  total_experiments: 10,
  created_at: 1546434027,
  updated_at: 1546434027,
  updated_by: 'dom',
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
  return (
    <React.Fragment>
      <div class="title">{data.name}</div>
      <div class="description">
        {data.description}
        <div class="sub-description">
          Created by {titleCase(data.created_by)} on{' '}
          {formatDate(data.created_at)}
          {data.updated_at !== data.created_at && (
            <div>
              Last Updated by {titleCase(data.updated_by)} on{' '}
              {formatDate(data.updated_at)}
            </div>
          )}
        </div>
      </div>
      <br />
      <div>
        <div class="label">Variants</div>
        {data.variants.map((v, i) => (
          <div key={i}>
            <span class="square-pills label-semi-muted">{v}</span>
          </div>
        ))}
      </div>

      <br />

      <div>
        <div class="label">Total Active Experiments</div>
        {
          <div>
            {data.active_experiments}
            <Link
              class="link m-l"
              to={`/razorx/experiments?feature_id=${data.id}&status=activated`}
            >
              View all
            </Link>
          </div>
        }
      </div>

      <br />

      <div>
        <div class="label">Total Experiments</div>
        {
          <div>
            {data.total_experiments}
            <Link
              class="link m-l"
              to={`/razorx/experiments?feature_id=${data.id}`}
            >
              View all
            </Link>
          </div>
        }
      </div>
    </React.Fragment>
  );
};
