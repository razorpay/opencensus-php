import { Link } from 'react-router-dom';
import { openModal, notifyError } from 'common/modal';
import { formatDate, titleCase } from 'common/util';
import { rexFetch } from 'admin/razorx/fetch';
import FeaturesModal from './FeaturesModal';

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

    rexFetch({ url: '/featureFlags/' + id })
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

  showFeatureModal = _ => {
    openModal(<FeaturesModal data={dummy_data} />);
  };

  showJSONModal = _ => {
    if (!window.CodeFlask) {
      notifyError('JSON Editor is missing. Reload page / check your Network!');
      return;
    }

    openModal(<FeaturesModal data={dummy_data} JSONView />);
  };

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
      content = (
        <Details
          data={data}
          terminate={this.terminate}
          showFeatureModal={this.showFeatureModal}
          showJSONModal={this.showJSONModal}
        />
      );
    }

    content = (
      <Details
        data={dummy_data}
        terminate={this.terminate}
        showFeatureModal={this.showFeatureModal}
        showJSONModal={this.showJSONModal}
      />
    );

    return <div class="entity-container">{content}</div>;
  }
}

const Details = ({ data, showFeatureModal, showJSONModal }) => {
  return (
    <div>
      <div class="sub-description">
        <span>
          <b>ID:</b> {data.id}
        </span>
        <span class="to-right">
          <a class="link text-bold" onClick={showFeatureModal}>
            Edit Feature
          </a>{' '}
          ({' '}
          <a class="link text-bold" onClick={showJSONModal}>
            RAW
          </a>{' '}
          )
        </span>
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
