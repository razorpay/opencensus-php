import { withRouter, Link } from 'react-router-dom';
import { openModal, notifyError } from 'common/modal';
import { formatDate, titleCase } from 'common/util';
import { rexFetch } from 'admin/razorx/fetch';
import FeaturesModal from './Modal';

import { AppStore } from 'admin/razorx/store';

@withRouter
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

    Promise.all([fetchFeature, ...this.getExperimentsFetchArray(id)])
      .then(
        ([
          feature,
          expLiveTotal,
          expTestTotal,
          expLiveCreated,
          expTestCreated,
          expActivated,
        ]) => {
          this.setState({
            isFetching: false,
          });

          if (feature) {
            this.setState({
              data: feature,
              experiments: {
                live: {
                  total: expLiveTotal && expLiveTotal.items.length,
                  created: expLiveCreated && expLiveCreated.items.length,
                },
                test: {
                  total: expTestTotal && expTestTotal.items.length,
                  created: expTestCreated && expTestCreated.items.length,
                },
                activated: expActivated.items[0],
              },
            });
          }
        }
      )
      .catch(({ errors }) => {
        this.setState({
          isFetching: false,
        });
      });
  }

  getExperimentsFetchArray(id) {
    const experimentsParams = {
      url: 'experiments',
      params: {
        count: 1,
        skip: 0,
        feature_id: id,
        mode: 'live',
      },
    };

    const liveTotal = rexFetch(experimentsParams);

    experimentsParams.mode = 'test';
    const testTotal = rexFetch(experimentsParams);

    experimentsParams.status = 'created';
    const testCreated = rexFetch(experimentsParams);

    experimentsParams.mode = 'live';
    const liveCreated = rexFetch(experimentsParams);

    delete experimentsParams.mode;
    experimentsParams.status = 'activated';
    const activeExperiment = rexFetch(experimentsParams);

    return [liveTotal, testTotal, liveCreated, testCreated, activeExperiment];
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

  goToExperiment = (mode, url) => {
    return () => {
      console.log('....', mode);
      AppStore.updateMode(mode);

      setTimeout(() => {
        this.props.history.push(url);
      });
    };
  };

  render() {
    const { isFetching, data, experiments } = this.state;
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
          experiments={experiments}
          goToExperiment={this.goToExperiment}
        />
      );
    }

    return <div class="entity-container">{content}</div>;
  }
}

const Details = ({
  data,
  showFeatureModal,
  showJSONModal,
  experiments,
  goToExperiment,
}) => {
  return (
    <div class="entity-details">
      <div class="sub-description">
        <span>
          <b>ID:</b> {data.id}
        </span>
        {!experiments.live.total &&
          !experiments.test.total && (
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
        {experiments.activated ? (
          <div>
            <div class="label">Active Experiment</div>

            <div class="sub-description column">
              <div>
                <b>ID: </b> {experiments.activated.id}
                <Link
                  class="link m-l"
                  to={`/experiments/${experiments.activated.id}?feature_id=${
                    data.id
                  }`}
                >
                  View
                </Link>
              </div>
            </div>
          </div>
        ) : (
          <div class="label">No Active Experiment</div>
        )}
      </div>

      <br />

      <div>
        <div class="label">Total Experiments</div>
        {
          <div>
            <div class="sub-description column">
              <div>
                <b>LIVE: </b> {experiments.live.total}
                {!!experiments.live.total && (
                  <a
                    class="link m-l"
                    onClick={goToExperiment(
                      'live',
                      `/experiments?feature_id=${data.id}`
                    )}
                  >
                    View
                  </a>
                )}
              </div>
              <div>
                <b>TEST: </b> {experiments.test.total}
                {!!experiments.test.total && (
                  <a
                    class="link m-l"
                    onClick={goToExperiment(
                      'test',
                      `/experiments?feature_id=${data.id}`
                    )}
                  >
                    View
                  </a>
                )}
              </div>
            </div>
          </div>
        }
      </div>

      <br />

      <div>
        <div class="label">Total Pending Experiments</div>
        {
          <div>
            <div class="sub-description column">
              <div>
                <b>LIVE: </b> {experiments.live.created}
                {!!experiments.live.created && (
                  <a
                    class="link m-l"
                    onClick={goToExperiment(
                      'live',
                      `/experiments?feature_id=${data.id}&status=created`
                    )}
                  >
                    View
                  </a>
                )}
              </div>
              <div>
                <b>TEST: </b> {experiments.test.created}
                {!!experiments.test.created && (
                  <a
                    class="link m-l"
                    onClick={goToExperiment(
                      'test',
                      `/experiments?feature_id=${data.id}&status=created`
                    )}
                  >
                    View
                  </a>
                )}
              </div>
            </div>
          </div>
        }
      </div>
    </div>
  );
};
