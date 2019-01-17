import { Link } from 'react-router-dom';
import { formatDate, titleCase } from 'common/util';
import { adminFetch } from 'common/fetch';

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

export default class extends React.Component {
  state = {};
  componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.fetch(nextProps.id);
    }
  }

  fetch(id) {
    this.setState({
      isFetching: true,
      data: null,
    });

    adminFetch({ url: '/featureFlags/' + id })
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

    content = <Details data={dummy_data} />;

    return <div class="entity-container">{content}</div>;
  }
}

const Details = ({ data }) => {
  return (
    <div>
      <div class="sub-description">
        <b>ID:</b> {data.id}
      </div>
      <div class="pad-highlight">
        <div class="title">{data.name}</div>
        <div class="description">
          {data.description}
          <div class="sub-description">
            <b>Created by</b> {titleCase(data.created_by)}{' '}
            <span class="inline-block">on {formatDate(data.created_at)}</span>
            {data.updated_at !== data.created_at && (
              <div>
                <b>Last Updated at</b>
                <span class="inline-block">
                  on {formatDate(data.updated_at)}
                </span>
              </div>
            )}
          </div>
        </div>
      </div>
      <br />
      <div>
        <div class="label">Variants</div>
        {data.variants.map((v, i) => (
          <div key={i}>
            <span class="square-pills">{v}</span>
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
              to={`/experiments?feature_id=${data.id}&status=activated`}
            >
              View Active Experiments
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
            <Link class="link m-l" to={`/experiments?feature_id=${data.id}`}>
              View All Experiments
            </Link>
          </div>
        }
      </div>
    </div>
  );
};
