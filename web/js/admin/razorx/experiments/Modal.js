import { observer } from 'mobx-react';
import debounce from 'rzp/utils/debounce';
import {
  openModal,
  closeModal,
  notifySuccess,
  notifyError,
} from 'common/modal';
import Form from 'ui/Form';
import Field, {
  TextAreaField,
  SwitchField,
  SelectField,
  SearchableSelectField,
} from 'ui/Field';
import { ModalContent } from 'component/Modal';
import JSONEdit from 'admin/razorx/JSONEdit';

import { rexFetch, rexPost, rexPatch } from 'admin/razorx/fetch';
import { AppStore } from 'admin/user';
import { initJSONObj, validatorJSON } from './validators';
import SegmentsList from './SegmentsList';

const COUNT = 10;
const MIN_NAME_TYPE = 2;

@observer
export default class extends React.Component {
  state = { featuresList: [] };

  componentWillMount() {
    const params = {};
    const data = this.props.data;

    if (data && data.id) {
      params.id = data.id;
    }

    this.fetchFeaturesList(params).then(list => {
      if (list) {
        this.defaultFeaturesList = list;
      }
    });
  }

  fetchFeaturesList(params) {
    this.setState({ variantsList: [] }); // Refresh variants list for new Feature list search

    return rexFetch({
      url: 'feature_flags',
      params: { ...params, count: COUNT },
    }).then(data => {
      data = {
        items: data.items,
        success: true,
      };
      if (data && data.success) {
        const featuresList = data.items.map(f => ({
          name: f.name,
          id: String(f.id),
          variants: f.variants,
        }));

        this.setState({ featuresList });

        return featuresList;
      }
    });
  }

  onSubmit = form => {
    const isEdit = this.props.data && this.props.data.id;
    const { description, environment, mode } = form;

    console.log('....FORM...', form);

    if (!description) {
      return notifyError('Description is required');
    }

    if (!this.state.selectedFeature) {
      return notifyError('Select a Feature');
    }

    const segments = [...this.state.segments];

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

      if (
        ['context-ramp', 'whitelist', 'blacklist'].indexOf(segment.type) > -1
      ) {
        if (typeof segment.ids === 'undefined' || !segment.ids) {
          msg = 'ids cannot be empty';
          break;
        }

        segment.ids = segment.ids
          .trim()
          .split(',')
          .map(id => id.trim());
      }

      if (['context-ramp', 'ramp'].indexOf(segment.type) > -1) {
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
      feature_id: this.state.selectedFeature.id,
      segments,
    };

    let requestFn = rexPost,
      url = 'experiments',
      successMsg = 'Experiment is successfully created';

    if (isEdit) {
      requestFn = rexPatch;
      url += `/${this.props.data.id}`;
      successMsg = `Experiment ${this.props.data.id} is successfully updated`;
    }

    requestFn({ url, data: reqPayload }).then(data => {
      if (data && data.success) {
        notifySuccess(successMsg);
        this.props.history.push('/experiments/' + data.id);
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
      Object.keys(initJSONObj).forEach(k => {
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

  debounce_searchInFeatureList = debounce(
    this.searchInFeatureList.bind(this),
    200
  );

  onInput = val => {
    if (val.length <= MIN_NAME_TYPE) {
      this.setState({ featuresList: this.defaultFeaturesList });
      return;
    }

    this.debounce_searchInFeatureList(val);
  };

  handleSelectFeature = ({ option }) => {
    this.setState({ selectedFeature: option, variantsList: option.variants });
  };

  onChangeSegmentsList = segments => {
    this.setState({
      segments,
    });
  };

  render() {
    const { data, JSONView } = this.props;
    const isEdit = !!(data && data.id);

    let header = data ? `Edit Experiment – ${data.id}` : 'Create Experiment';

    if (JSONView) {
      header += ' (JSON)';
    }

    this.JSONObj.mode = AppStore.mode;

    const { featuresList, selectedFeature } = this.state;

    return (
      <ModalContent class="modal-experiments modal-json-edit" header={header}>
        <Form onSubmit={this.onSubmit} class="full-span full-elements">
          {JSONView ? (
            <JSONEdit
              initialJSON={this.JSONObj}
              validatorJSON={validatorJSON}
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
                <option value="production">Production</option>
                <option value="beta">Beta</option>
              </SelectField>

              <TextAreaField
                label="Description"
                name="description"
                defaultValue={isEdit ? data.description : ''}
                required
              />
              <SearchableSelectField
                selectedOptionLabelPath="name"
                searchIndices={['id', 'name']}
                label="Feature"
                trackBy="id"
                options={featuresList || []}
                selected={selectedFeature}
                name="feature_id"
                defaultValue={isEdit ? data.feature_id : ''}
                onInput={this.onInput}
                onChange={this.handleSelectFeature}
                beforeOptionsComponent={() => <div class="heading">Recent</div>}
              />

              {selectedFeature && (
                <React.Fragment>
                  <div class="sub-heading">Segments</div>
                  <SegmentsList
                    variantsList={selectedFeature.variants}
                    onChange={this.onChangeSegmentsList}
                  />
                </React.Fragment>
              )}

              <div style={{ marginTop: 24 }} />
            </React.Fragment>
          )}
          <div class="footer">
            <button class="btn btn--primary" disabled={!this.isValid()}>
              Create
              <span class="spin-btn" />
            </button>
          </div>
        </Form>
      </ModalContent>
    );
  }
}
