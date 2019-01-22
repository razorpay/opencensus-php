import { observer } from 'mobx-react';
import debounce from 'rzp/utils/debounce';
import { openModal, closeModal, notifySuccess } from 'common/modal';
import Form from 'ui/Form';
import Field, {
  TextAreaField,
  SwitchField,
  SelectField,
  SearchableSelectField,
} from 'ui/Field';
import { ModalContent } from 'component/Modal';
import JSONEdit from 'admin/razorx/JSONEdit';

import { rexFetch } from 'admin/razorx/fetch';

import { AppStore } from 'admin/user';

const initJSONObj = {
  description: '',
  environment: 'beta',
  mode: 'test',
  feature_id: 0,
  segments: [
    {
      variant: '',
      type: '// Eg: String: whitelist, blacklist, ramp, context-ramp',
      ids: [],
      weight: '// Eg: Number: 1(=> 0.001%)',
    },
  ],
};

const validatorJSON = {
  description: function(val) {
    if (!val || typeof val !== 'string') {
      return 'description must be non-empty String';
    }
  },
  environment: function(val) {
    if (!val || ['production', 'beta'].indexOf(val) === -1) {
      return 'environment must be one of [production, beta]';
    }
  },
  mode: function(val) {
    if (!val || ['test', 'live'].indexOf(val) === -1) {
      return 'mode must be one of [test, live]';
    }
  },
  feature_id: function(val) {
    if (typeof val === 'undefined' || typeof val !== 'number') {
      return 'feature id must be a valid Number';
    }
  },
  segments: function(val) {
    if (!val || !(val instanceof Array || !val.length)) {
      return 'segments must be a non-empty Array';
    }

    let errorMsg;

    val.forEach(s => {
      const isVariantInvalid = !s.variant || typeof s.variant !== 'string';
      if (isVariantInvalid) {
        errorMsg = 'variant must be a non-empty String';
        return false;
      }

      const isTypeInvalid =
        !s.type ||
        ['whitelist', 'blacklist', 'ramp', 'context-ramp'].indexOf(s.type) ===
          -1;
      if (isTypeInvalid) {
        errorMsg =
          'type must be one of [whitelist, blacklist, ramp, context-ramp]';
        return false;
      }

      const isIdInvalid = !s.ids || !(s.ids instanceof Array);
      if (isIdInvalid) {
        errorMsg = 'Invalid Array of ids';
        return false;
      }

      const isWeightInvalid =
        !s.weight ||
        typeof s.weight !== 'number' ||
        s.weight < 1 ||
        s.weight > 100000;
      if (isWeightInvalid) {
        errorMsg = 'weight must be a valid Number between [1-100000]';
        return false;
      }
    });

    return errorMsg;
  },
};

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

    this.fetchFeaturesList(params).then(data => {
      if (data) {
        this.defaultFeaturesList = data.items;
      }
    });
  }

  fetchFeaturesList(params) {
    return rexFetch({
      url: 'featureFlags',
      params: { ...params, count: COUNT },
    }).then(data => {
      if (data && data.success) {
        this.setState({ featuresList: data.items });

        return data;
      }
    });
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

  onSubmit = data => {};

  isValid() {
    if (this.props.JSONView) {
      // Check if JSON is valid and all required params are there
    } else {
      // Check if all required params are there
    }

    return true;
  }

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
    this.setState({ selectedFeature: option });
  };

  render() {
    const { data, JSONView } = this.props;
    let header = data ? 'Edit Experiment' : 'Create Experiment';

    const isEdit = !!(data && data.id);

    if (JSONView) {
      header += ' (JSON)';
    }

    if (isEdit) {
      header += ` – ${data.id}`;
    }
    this.JSONObj.mode = AppStore.mode;

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
                options={this.state.featuresList || []}
                selected={this.state.selectedFeature}
                name="feature_id"
                defaultValue={isEdit ? data.feature_id : ''}
                onInput={this.onInput}
                onChange={this.handleSelectFeature}
                beforeOptionsComponent={() => <div class="heading">Recent</div>}
              />

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
