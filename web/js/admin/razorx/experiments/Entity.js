import { Link } from 'react-router-dom';
import { formatDate, titleCase, classList } from 'common/util';
import { razorxFetch } from 'admin/razorx/fetch';

const dummy_data = {
  id: 201,
  description: 'Random description for this experiment',
  environment: 'production',
  mode: 'test',
  feature_name: 'Some Random name',
  feature_id: 182,
  segments: [
    {
      variant: 'on',
      type: 'whitelist',
      ids: ['merchant1', 'merchant2'],
      weight: 0,
    },
    {
      variant: 'on',
      type: 'context-ramp',
      ids: ['merchant3', 'merchant5'],
      weight: 5,
    },
    {
      variant: 'off',
      type: 'ramp',
      ids: ['random'],
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
    const { id } = this.props;

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
      content = <Details data={data} />;
    }

    content = <Details data={dummy_data} />;

    return <div class="entity-container">{content}</div>;
  }
}

const Details = ({ data }) => {
  const segments = getSegmentsGroupedByVariant(data.segments);

  return (
    <div>
      <div class="sub-description">
        <b>ID:</b> {data.id}
      </div>
      <div class="description">
        {data.description}
        <div class="sub-description">
          <b>Created by</b> {titleCase(data.created_by)} on{' '}
          {formatDate(data.created_at)}
          {data.activated_at && (
            <div>
              <b>Activated by</b> {titleCase(data.activated_by)} on{' '}
              {formatDate(data.activated_at)}
            </div>
          )}
          {data.updated_at !== data.created_at && (
            <div>
              <b>Last Updated by</b> {titleCase(data.updated_by)} on{' '}
              {formatDate(data.updated_at)}
            </div>
          )}
          {data.terminated_at && (
            <div>
              <b>Terminated by</b> {titleCase(data.terminated_by)} on{' '}
              {formatDate(data.terminated_at)}
            </div>
          )}
        </div>
      </div>

      <br />

      <div>
        <div class="label">Feature</div>
        {data.feature_name} <br />
        <Link class="link" to={`/razorx/features/${data.feature_id}`}>
          View Feature
        </Link>
        <Link
          class="link m-l"
          to={`/razorx/experiments?feature_id=${data.feature_id}`}
        >
          View All Experiments
        </Link>
      </div>

      <br />

      <div>
        <div class="label">Environment</div>
        <span class="square-pills label-semi-muted">{data.environment}</span>
      </div>

      <br />

      <div>
        <div class="label">Mode</div>
        <span class="square-pills label-semi-muted">{data.mode}</span>
      </div>

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
