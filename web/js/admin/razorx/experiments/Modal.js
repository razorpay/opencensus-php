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

    this.fetchFeaturesList(params).then(data => {
      if (data) {
        this.defaultFeaturesList = data.items;
      }
    });
  }

  fetchFeaturesList(params) {
    this.setState({ variantsList: [] }); // Refresh variants list for new Feature list search

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

  onSubmit = form => {
    const isEdit = this.props.data && this.props.data.id;
    console.log('....FORM...', form);

    return; // ..TESTING..

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

  onChangeSegmentsList = list => {
    console.log('....onChangeSegmentsList...', list);
  };

  render() {
    const { data, JSONView } = this.props;
    const isEdit = !!(data && data.id);

    let header = data ? `Edit Experiment – ${data.id}` : 'Create Experiment';

    if (JSONView) {
      header += ' (JSON)';
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

              <div class="sub-heading">Segments</div>

              <SegmentsList
                variantsList={this.props.variantsList}
                onChange={this.onChangeSegmentsList}
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
