import { withRouter, Link } from 'react-router-dom';
import { openModal, notifyError } from 'razorx/components/Modal';
import { titleCase } from 'common/utils/rzp-utils';
import { formatDate } from 'razorx/helpers/utils';
import { rexFetch } from 'razorx/helpers/fetch';
import FeaturesModal from './Modal';

import { AppStore } from 'razorx/store';
import ExperimentsModal from 'razorx/views/Experiments/Modal';

@withRouter
export default class extends React.Component {
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

    rexFetch({ url: 'feature_flags/' + id })
      .then(resp => {
        this.setState({
          isFetching: false,
        });

        if (resp) {
          this.setState({
            data: resp,
          });
        }
      })
      .catch(err => {
        this.setState({
          isFetching: false,
        });
      });

    Promise.all(this.getExperimentsFetchArray(id)).then(
      ([
        expLiveTotal,
        expTestTotal,
        expLiveCreated,
        expTestCreated,
        expActivated,
      ]) => {
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
      }
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

  showExperimentModal = isJSONView => {
    if (!isJSONView) {
      return openModal(<ExperimentsModal feature={this.state.data} />);
    }

    if (!window.CodeFlask) {
      notifyError('JSON Editor is missing. Reload page / check your Network!');
      return;
    }

    openModal(<ExperimentsModal feature={this.state.data} JSONView />);
  };

  showFeatureModal = _ => {
    openModal(<FeaturesModal data={this.state.data} onEdit={this.onEdit} />);
  };

  showJSONModal = _ => {
    if (!window.CodeFlask) {
      notifyError('JSON Editor is missing. Reload page / check your Network!');
      return;
    }

    openModal(
      <FeaturesModal data={this.state.data} onEdit={this.onEdit} JSONView />
    );
  };

  onEdit = data => {
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
          showExperimentModal={this.showExperimentModal}
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
  showExperimentModal,
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
        {experiments &&
          !experiments.live.total &&
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

      {experiments && (
        <React.Fragment>
          <div>
            {experiments.activated ? (
              <div>
                <div class="label">Active Experiment</div>

                <div class="sub-description column">
                  <div>
                    <b>ID: </b> {experiments.activated.id}
                    <Link
                      class="link m-l"
                      to={`/experiments/${
                        experiments.activated.id
                      }?feature_id=${data.id}`}
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

          <div>
            <a className="link text-bold" onClick={_ => showExperimentModal()}>
              Create Experiment
            </a>{' '}
            ({' '}
            <a
              className="link text-bold"
              onClick={_ => showExperimentModal(true)}
            >
              RAW
            </a>{' '}
            )
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
        </React.Fragment>
      )}
    </div>
  );
};
