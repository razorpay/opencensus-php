import { Link } from 'react-router-dom';
import { openModal, notifyError } from 'common/modal';
import { formatDate, titleCase } from 'common/util';
import { rexFetch } from 'admin/razorx/fetch';
import FeaturesModal from './Modal';

export default class extends React.Component {
  state = {};
  componentDidMount() {
    this.fetch(this.props.id);
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.fetch(nextProps.id);
    }
  }

  fetch(id) {
    if (!id) {
      return;
    }

    this.setState({
      isFetching: true,
      data: null,
    });

    const fetchFeature = rexFetch({ url: 'feature_flags/' + id });
    const featureExperimentLive = rexFetch({
      url: 'experiments',
      params: {
        count: 1,
        skip: 0,
        feature_id: id,
        mode: 'live',
      },
    });
    const featureExperimentTest = rexFetch({
      url: 'experiments',
      params: {
        count: 1,
        skip: 0,
        feature_id: id,
        mode: 'live',
      },
    });

    Promise.all([fetchFeature, featureExperimentLive, featureExperimentTest])
      .then(([feature, experimentLive, experimentTest]) => {
        this.setState({
          isFetching: false,
        });

        if (feature) {
          this.setState({
            data: feature,
            hasExperiment:
              !!(experimentLive && experimentLive.items.length) ||
              (!!experimentTest && experimentTest.items.length),
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
    openModal(<FeaturesModal data={this.state.data} />);
  };

  showJSONModal = _ => {
    if (!window.CodeFlask) {
      notifyError('JSON Editor is missing. Reload page / check your Network!');
      return;
    }

    openModal(<FeaturesModal data={this.state.data} JSONView />);
  };

  render() {
    const { isFetching, data, hasExperiment } = this.state;
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
          hasExperiment={hasExperiment}
        />
      );
    }

    return <div class="entity-container">{content}</div>;
  }
}

const Details = ({ data, showFeatureModal, showJSONModal, hasExperiment }) => {
  return (
    <div class="entity-details">
      <div class="sub-description">
        <span>
          <b>ID:</b> {data.id}
        </span>
        {!hasExperiment && (
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
        )}
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
