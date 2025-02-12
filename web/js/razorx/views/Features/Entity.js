import React from 'react';
import { Link } from 'react-router-dom';
import { withRouter } from 'common/deprecated/withRouter';
import { openModal, notifyError } from 'razorx/components/Modal';
import { titleCase } from 'common/utils/rzp-utils';
import { formatDate } from 'razorx/helpers/utils';
import { rexFetch } from 'razorx/helpers/fetch';
import FeaturesModal from './Modal';

import { AppStore } from 'razorx/store';
import ExperimentsModal from 'razorx/views/Experiments/Modal';

class Entity extends React.Component {
  state = {};
  componentDidMount() {
    this.fetch(this.props.id);
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
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
      experiments: null,
    });

    rexFetch({ url: `feature_flags/${id}` })
      .then((resp) => {
        this.setState({
          isFetching: false,
        });

        if (resp) {
          this.setState({
            data: resp,
          });
        }
      })
      .catch((_) => {
        this.setState({
          isFetching: false,
        });
      });

    Promise.all(this.getExperimentsFetchArray(id)).then(
      ([expLiveTotal, expTestTotal, expLiveCreated, expTestCreated, expActivated]) => {
        this.setState({
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
      },
    );
  }

  getExperimentsFetchArray(id) {
    const experimentsParams = {
      url: 'experiments',
      params: {
        count: 1,
        skip: 0,
        feature_id: id,
      },
    };

    let liveTotal = { ...experimentsParams };
    liveTotal.params.mode = 'live';
    liveTotal = rexFetch(liveTotal);

    let testTotal = { ...experimentsParams };
    testTotal.params.mode = 'test';
    testTotal = rexFetch(testTotal);

    let testCreated = { ...experimentsParams };
    testCreated.params.mode = 'test';
    testCreated.params.status = 'created';
    testCreated = rexFetch(testCreated);

    let liveCreated = { ...experimentsParams };
    liveCreated.params.mode = 'live';
    liveCreated.params.status = 'created';
    liveCreated = rexFetch(liveCreated);

    let activeExperiment = { ...experimentsParams };
    activeExperiment.params.status = 'activated';
    activeExperiment = rexFetch(activeExperiment);

    return [liveTotal, testTotal, liveCreated, testCreated, activeExperiment];
  }

  showExperimentModal = () => (isJSONView) => {
    if (!isJSONView) {
      return openModal(<ExperimentsModal feature={this.state.data} />);
    }

    if (!window.CodeFlask) {
      notifyError('JSON Editor is missing. Reload page / check your Network!');
      return null;
    }

    return openModal(<ExperimentsModal feature={this.state.data} JSONView />);
  };

  showFeatureModal = (_) => {
    openModal(<FeaturesModal data={this.state.data} onEdit={this.onEdit} />);
  };

  showJSONModal = (_) => {
    if (!window.CodeFlask) {
      notifyError('JSON Editor is missing. Reload page / check your Network!');
      return;
    }

    openModal(<FeaturesModal data={this.state.data} onEdit={this.onEdit} JSONView />);
  };

  onEdit = (data) => {
    this.setState({
      data,
    });
  };

  goToExperiment = (mode, url) => {
    return () => {
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
      content = <div className="spinner center" />;
    } else if (!isFetching && !data) {
      content = (
        <div className="page-center empty-entity">
          <i className="i-layers" />
          <div className="description">
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
          showExperimentModal={this.showExperimentModal}
          showJSONModal={this.showJSONModal}
          experiments={experiments}
          goToExperiment={this.goToExperiment}
        />
      );
    }

    return <div className="entity-container">{content}</div>;
  }
}

const Details = ({
  data,
  showFeatureModal,
  showExperimentModal,
  showJSONModal,
  experiments,
  goToExperiment,
}) => {
  return (
    <div className="entity-details">
      <div className="sub-description">
        <span>
          <b>ID:</b> {data.id}
        </span>
        {experiments && !experiments?.live?.total ? (
          <span className="to-right">
            <a className="link text-bold" onClick={showFeatureModal}>
              Edit Feature
            </a>{' '}
            ({' '}
            <a className="link text-bold" onClick={showJSONModal}>
              RAW
            </a>{' '}
            )
          </span>
        ) : null}
      </div>

      <div className="pad-highlight">
        <div className="title">{data.name}</div>
        <div className="description">
          {data.description}
          <div className="sub-description">
            <b>Created by</b> {titleCase(data.created_by)}{' '}
            <span className="inline-block">on {formatDate(data.created_at)}</span>
            {data.updated_at !== data.created_at && (
              <div>
                <b>Last Updated at</b>
                <span className="inline-block">on {formatDate(data.updated_at)}</span>
              </div>
            )}
          </div>
        </div>
      </div>
      <br />
      <div>
        <div className="label">Variants</div>
        {data.variants.map((v, i) => (
          <div key={i}>
            <span className="square-pills">{v}</span>
          </div>
        ))}
      </div>

      <br />

      {experiments && (
        <React.Fragment>
          <div>
            {experiments.activated ? (
              <div>
                <div className="label">Active Experiment</div>

                <div className="sub-description column">
                  <div>
                    <b>ID: </b> {experiments.activated.id}
                    <Link
                      className="link m-l"
                      to={`/experiments/${experiments.activated.id}?feature_id=${data.id}`}
                    >
                      View
                    </Link>
                  </div>
                </div>
              </div>
            ) : (
              <div className="label">No Active Experiment</div>
            )}
          </div>

          <div>
            <a className="link text-bold" onClick={showExperimentModal()}>
              Create Experiment
            </a>{' '}
            ({' '}
            <a className="link text-bold" onClick={showExperimentModal(true)}>
              RAW
            </a>{' '}
            )
          </div>

          <br />

          <div>
            <div className="label">Total Experiments</div>
            {
              <div>
                <div className="sub-description column">
                  <div>
                    <b>LIVE: </b> {experiments.live.total}
                    {!!experiments.live.total && (
                      <a
                        className="link m-l"
                        onClick={goToExperiment('live', `/experiments?feature_id=${data.id}`)}
                      >
                        View
                      </a>
                    )}
                  </div>
                  <div>
                    <b>TEST: </b> {experiments.test.total}
                    {!!experiments.test.total && (
                      <a
                        className="link m-l"
                        onClick={goToExperiment('test', `/experiments?feature_id=${data.id}`)}
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
            <div className="label">Total Pending Experiments</div>
            {
              <div>
                <div className="sub-description column">
                  <div>
                    <b>LIVE: </b> {experiments.live.created}
                    {!!experiments.live.created && (
                      <a
                        className="link m-l"
                        onClick={goToExperiment(
                          'live',
                          `/experiments?feature_id=${data.id}&status=created`,
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
                        className="link m-l"
                        onClick={goToExperiment(
                          'test',
                          `/experiments?feature_id=${data.id}&status=created`,
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
        </React.Fragment>
      )}
    </div>
  );
};

export default withRouter(Entity);
