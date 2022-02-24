import { Link } from 'react-router-dom';
import { openModal, notifyError, notifySuccess } from 'razorx/components/Modal';
import { classList, titleCase } from 'common/utils/rzp-utils';
import { formatDate } from 'razorx/helpers/utils';
import { rexFetch, rexPatch } from 'razorx/helpers/fetch';
import AsyncButton from 'razorx/components/ui/AsyncButton';
import ExperimentsModal from './Modal';

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
      feature: null,
    });

    rexFetch({ url: 'experiments/' + id })
      .then(resp => {
        this.setState({
          isFetching: false,
        });

        if (resp) {
          this.setState({
            data: resp,
          });

          this.props.updateEntityData && this.props.updateEntityData(resp);

          // Fetch corresponding feature
          rexFetch({ url: 'feature_flags/' + resp.feature_id }).then(resp => {
            if (resp) {
              this.setState({
                feature: resp,
              });
            }
          });
        }
      })
      .catch(({ errors }) => {
        this.setState({
          isFetching: false,
        });
      });
  }

  activate = id => {
    return rexPatch(`experiments/${id}/activate`).then(resp => {
      if (resp) {
        const successMsg = resp.workflow_id
          ? `Workflow ${resp.workflow_id} is created`
          : 'Experiment is successfully Activated';
        notifySuccess(successMsg);
      }
    });
  };

  terminate = id => {
    return rexPatch(`experiments/${id}/terminate`).then(resp => {
      if (resp) {
        notifySuccess('Experiment is successfully Terminated');

        // Update view on terminating experiment
        this.setState({
          data: resp,
        });
      }
    });
  };

  showExperimentModal = _ => {
    openModal(
      <ExperimentsModal
        data={this.state.data}
        feature={this.state.feature}
        onEdit={this.onEdit}
      />
    );
  };

  showJSONModal = _ => {
    if (!window.CodeFlask) {
      notifyError('JSON Editor is missing. Reload page / check your Network!');
      return;
    }

    openModal(
      <ExperimentsModal
        data={this.state.data}
        onEdit={this.onEdit}
        isReadOnly={this.props.isReadOnly || this.state.data.activated_at}
        JSONView
      />
    );
  };

  onEdit = data => {
    this.setState({
      data,
    });
  };

  render() {
    const { isFetching, data, feature } = this.state;
    const { id, isReadOnly, isCustomLayout } = this.props;

    let content;

    if (!id) {
      content = null;
    } else if (isFetching) {
      content = <div class="spinner center" />;
    } else if (!isFetching && !data) {
      content = (
        <div class="page-center empty-entity">
          <i class="i-flask" />
          <div class="description">
            <div>ID: {id}</div>
            No Experiment found!
          </div>
        </div>
      );
    } else {
      content = (
        <Details
          data={data}
          feature={feature}
          activate={this.activate}
          terminate={this.terminate}
          showExperimentModal={this.showExperimentModal}
          showJSONModal={this.showJSONModal}
          isReadOnly={isReadOnly}
          isCustomLayout={isCustomLayout}
        />
      );
    }

    return (
      <div
        class={classList(
          'entity-container',
          isCustomLayout && 'entity-container--custom'
        )}
      >
        {content}
      </div>
    );
  }
}

const Details = ({
  data,
  feature,
  activate,
  terminate,
  showExperimentModal,
  showJSONModal,
  isReadOnly,
  isCustomLayout,
}) => {
  const segments = getSegmentsGroupedByVariant(data.segments);

  return (
    <div class="entity-details">
      <div class="sub-description">
        <span>
          <b>ID:</b> {data.id}
        </span>
        {feature && (
          <span class="to-right">
            {!data.activated_at &&
              !isReadOnly && (
                <>
                  <a class="link text-bold" onClick={showExperimentModal}>
                    Edit Experiment
                  </a>{' '}
                </>
              )}
            ({' '}
            <a class="link text-bold" onClick={showJSONModal}>
              RAW
            </a>{' '}
            )
          </span>
        )}
      </div>

      <div class="pad-highlight">
        <div class="description">
          {data.description}
          <div class="sub-description">
            <b>Created by</b> {titleCase(data.created_by)} on{' '}
            {formatDate(data.created_at)}
            {!!data.activated_at && (
              <div>
                <b>Activated by</b> {titleCase(data.activated_by)}{' '}
                <span class="inline-block">
                  on {formatDate(data.activated_at)}
                </span>
              </div>
            )}
            {data.updated_at !== data.created_at && (
              <div>
                <b>Last Updated by</b> {titleCase(data.updated_by)}{' '}
                <span class="inline-block">
                  on {formatDate(data.updated_at)}
                </span>
              </div>
            )}
            {!!data.terminated_at && (
              <div class="text-danger" style={{ opacity: 0.8 }}>
                <b>Terminated by</b> {titleCase(data.terminated_by)}
                <span class="inline-block">
                  {' '}
                  on {formatDate(data.terminated_at)}
                </span>
              </div>
            )}
          </div>
        </div>
      </div>

      {/* Experiment is in pending state */}
      {!isCustomLayout &&
        !data.activated_at &&
        !data.terminated_at && (
          <React.Fragment>
            <br />
            <div>
              <AsyncButton
                class="link danger text-danger text-bold"
                pendingClass="link danger-faded text-danger text-bold btn-pending"
                confirm={`Do you want to Activate Experiment id "${data.id}"?`}
                onClick={_ => activate(data.id)}
              >
                Activate Experiment
                <span class="dot-loader">.</span>
              </AsyncButton>
            </div>
          </React.Fragment>
        )}

      <br />

      <div>
        <div class="label">Feature</div>
        {feature && (
          <div class="sub-description column">
            <div>
              <b>ID: </b> {feature.id}
            </div>
            <div>
              <b>NAME: </b> {feature.name}
            </div>
          </div>
        )}
        <Link class="link" to={`/features_flags/${data.feature_id}`}>
          View Feature
        </Link>
        {!isCustomLayout && (
          <Link
            class="link m-l"
            to={`/experiments?feature_id=${data.feature_id}`}
          >
            View All Experiments
          </Link>
        )}
      </div>

      <br />

      <div class="flex-row">
        <div class="flex-row-item">
          <div class="label">Environment</div>
          <span class="square-pills label-semi-muted">{data.environment}</span>
        </div>

        <div class="flex-row-item">
          <div class="label">Mode</div>
          <span class="square-pills label-semi-muted">{data.mode}</span>
        </div>
      </div>

      {!isCustomLayout &&
        !!data.activated_at &&
        !data.terminated_at && (
          <React.Fragment>
            <br />
            <div>
              <AsyncButton
                class="link danger text-danger text-bold"
                pendingClass="link danger-faded text-danger text-bold btn-pending"
                confirm={`Do you want to terminate Experiment id "${data.id}"?`}
                onClick={_ => terminate(data.id)}
              >
                Terminate Experiment
                <span class="dot-loader">.</span>
              </AsyncButton>
            </div>
          </React.Fragment>
        )}
      <div class="separator" />

      <div>
        <div class="title">Segments</div>

        {Object.keys(segments).map((k, i) => {
          const segment = segments[k];

          return (
            <div key={i} class="segment">
              <div>
                <span class="square-pills label-semi-muted">{k}</span>
              </div>
              {segment.map((s, j) => (
                <div class="sub-segment" key={j}>
                  {Object.keys(s).map((g, ix) => {
                    if (s[g] === null) {
                      return;
                    }

                    const isArray = s[g] instanceof Array;

                    return (
                      <div key={ix}>
                        <span class="label">{titleCase(g)}: </span>
                        <span class={classList(isArray && 'sub-segment-group')}>
                          {isArray ? s[g].join(', ') : s[g]}
                        </span>
                      </div>
                    );
                  })}
                </div>
              ))}
            </div>
          );
        })}
      </div>
    </div>
  );
};

function getSegmentsGroupedByVariant(data) {
  const bucket = {};

  data.forEach(s => {
    const seg = { ...s };

    if (bucket.hasOwnProperty(seg.variant)) {
      bucket[seg.variant].push(seg);
      delete seg.variant;
    } else {
      bucket[seg.variant] = [seg];
      delete seg.variant;
    }
  });

  return bucket;
}
