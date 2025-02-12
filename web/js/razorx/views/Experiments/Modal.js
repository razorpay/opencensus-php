/* eslint-disable */
import { withRouter } from 'common/deprecated/withRouter';
import { observer } from 'mobx-react';
import debounce from 'common/utils/debounce';
import { closeModal, notifySuccess, notifyError } from 'razorx/components/Modal';
import Form from 'razorx/components/ui/Form';
import Field, {
  TextAreaField,
  SwitchField,
  SelectField,
  SearchableSelectField,
} from 'razorx/components/ui/Field';
import { ModalContent } from 'common/new-ui/Modal';
import JSONEdit from 'razorx/components/JSONEdit';

import { rexFetch, rexPost, rexPut } from 'razorx/helpers/fetch';
import { AppStore } from 'razorx/store';
import validatorJSON, { initJSONObj } from './validators';
import SegmentsList from './SegmentsList';

const COUNT = 10;
const MIN_NAME_TYPE = 2;

@observer
class ExModal extends React.Component {
  state = this.initState();

  initState() {
    const isEdit = !!this.props.data;
    let selectedFeature = null;
    let state = {};

    if (this.props.feature) {
      selectedFeature = { ...this.props.feature };
      selectedFeature.id = String(selectedFeature.id);
      state.selectedFeature = selectedFeature;
    }

    if (isEdit) {
      state.featuresList = [selectedFeature];
      state.segments = this.props.data.segments;
    }

    return state;
  }

  UNSAFE_componentWillMount() {
    const isEdit = !!this.props.data;

    if (!isEdit) {
      this.fetchFeaturesList().then((list) => {
        if (list) {
          this.defaultFeaturesList = list;
        }
      });
    }
  }

  fetchFeaturesList(params) {
    return rexFetch({
      url: 'feature_flags',
      params: { ...params, count: COUNT },
    }).then((resp) => {
      if (resp) {
        const featuresList = resp.items.map((f) => ({
          name: f.name,
          id: String(f.id),
          variants: f.variants,
        }));

        this.setState({ featuresList });

        return featuresList;
      }
    });
  }

  onSubmit = (form) => {
    const isEdit = this.props.data && this.props.data.id;
    let data = form,
      segments;

    const isJSONView = this.props.JSONView;
    if (isJSONView) {
      data = JSON.parse(form['json-value']);
      segments = data.segments;
    } else {
      if (!this.state.selectedFeature) {
        return notifyError('Select a Feature');
      }

      segments = this.state.segments.map((s) => ({ ...s }));
    }

    const { description, environment, mode } = data;

    if (!description) {
      return notifyError('Description is required');
    }

    let msg = '';
    for (let i = 0; i < segments.length; i++) {
      const segment = segments[i];

      if (typeof segment.variant === 'undefined') {
        msg = 'variant cannot be empty';
        break;
      } else if (typeof segment.type === 'undefined') {
        msg = 'type cannot be empty';
        break;
      }

      if (['contextramp', 'whitelist', 'blacklist'].indexOf(segment.type) > -1) {
        if (typeof segment.ids === 'undefined' || !segment.ids) {
          msg = 'ids cannot be empty';
          break;
        }

        segment.ids = segment.ids
          .trim()
          .split(',')
          .map((id) => id.trim());
      }

      if (['contextramp', 'ramp'].indexOf(segment.type) > -1) {
        if (typeof segment.weight === 'undefined') {
          msg = 'weight cannot be empty';
          break;
        } else if (segment.weight < 1 || segment.weight > 100000) {
          msg = 'weight must be between [1,100000] inclusive';
          break;
        }
      }
    }

    if (msg) {
      notifyError(msg);
      return;
    }

    const reqPayload = {
      description,
      environment,
      mode,
      segments,
    };

    // Feature id cannot be edited once tied with experiment
    if (!isEdit) {
      reqPayload.feature_id = isJSONView
        ? Number(data.feature_id)
        : Number(this.state.selectedFeature.id);
    }

    let requestFn = rexPost,
      url = 'experiments',
      successMsg = 'Experiment is successfully created';

    if (isEdit) {
      requestFn = rexPut;
      url += `/${this.props.data.id}`;
      successMsg = `Experiment ${this.props.data.id} is successfully updated`;
    }

    requestFn({ url, data: reqPayload }).then((resp) => {
      if (resp) {
        closeModal();
        notifySuccess(successMsg);

        if (!isEdit) {
          this.props.history.push('/experiments/' + resp.id);
        } else {
          this.props.onEdit(resp);
        }
      }
    });
  };

  isValid() {
    if (this.props.JSONView) {
      // Check if JSON is valid and all required params are there
    } else {
      // Check if all required params are there
    }

    return true;
  }

  JSONObj = (() => {
    let prepareObj = {};

    if (this.props.data) {
      Object.keys(initJSONObj).forEach((k) => {
        prepareObj[k] = this.props.data[k];
      });
    } else {
      prepareObj = { ...initJSONObj };
    }

    return prepareObj;
  })();

  searchInFeatureList(val) {
    this.fetchFeaturesList({ name: val });
  }

  debounce_searchInFeatureList = debounce(this.searchInFeatureList.bind(this), 200);

  onInput = (val) => {
    if (val.length <= MIN_NAME_TYPE) {
      this.setState({ featuresList: this.defaultFeaturesList });
      return;
    }

    this.debounce_searchInFeatureList(val);
  };

  handleSelectFeature = ({ option }) => {
    this.setState({ selectedFeature: option, segments: null });
  };

  onChangeSegmentsList = (segments) => {
    this.setState({
      segments,
    });
  };

  render() {
    const { data, feature, JSONView, isReadOnly } = this.props;
    const isEdit = !!(data && data.id);

    let header = data ? `Edit Experiment – ${data.id}` : 'Create Experiment';

    if (JSONView) {
      header += ' (JSON)';
    }

    this.JSONObj.mode = AppStore.mode;

    const { featuresList, selectedFeature } = this.state;

    return (
      <ModalContent className="modal-experiments modal-json-edit" header={header}>
        <Form onSubmit={this.onSubmit} className="full-span full-elements">
          {JSONView ? (
            <JSONEdit
              initialJSON={this.JSONObj}
              validatorJSON={validatorJSON}
              isReadOnly={isReadOnly}
            />
          ) : (
            <React.Fragment>
              <SwitchField
                name="mode"
                label="Mode"
                defaultValue={isEdit ? data.mode.toLowerCase() : AppStore.mode}
                disabledLabel="Test"
                enabledLabel="Live"
                enabledValue="live"
                disabledValue="test"
              />
              <SelectField
                name="environment"
                label="Environment"
                defaultValue={isEdit ? data.environment : 'production'}
                required
              >
                {AppStore.environmentList.map((e, i) => (
                  <option key={i} value={e}>
                    {e}
                  </option>
                ))}
              </SelectField>

              <TextAreaField
                label="Description"
                name="description"
                defaultValue={isEdit ? data.description : ''}
                required
              />
              {isEdit ? (
                <Field label="Feature" name="feature_id" defaultValue={feature.name} readOnly />
              ) : (
                <SearchableSelectField
                  name="feature_id"
                  selectedOptionLabelPath="name"
                  placeholder="At least 2 characters"
                  searchIndices={['id', 'name']}
                  label="Feature"
                  trackBy="id"
                  options={featuresList || []}
                  selected={selectedFeature}
                  onInput={this.onInput}
                  onChange={this.handleSelectFeature}
                  beforeOptionsComponent={() => <div className="heading">Recent</div>}
                />
              )}

              {selectedFeature && (
                <div key={selectedFeature.id}>
                  <div className="sub-heading">Segments</div>
                  <SegmentsList
                    variantsList={selectedFeature.variants}
                    onChange={this.onChangeSegmentsList}
                    defaultValue={this.state.segments}
                  />
                </div>
              )}

              <div style={{ marginTop: 24 }} />
            </React.Fragment>
          )}
          <div className="footer">
            {!isReadOnly && (
              <button className="btn btn--primary" disabled={!this.isValid()}>
                {isEdit ? 'Update' : 'Create'}
                <span className="spin-btn" />
              </button>
            )}
          </div>
        </Form>
      </ModalContent>
    );
  }
}

export default withRouter(ExModal);
