import debounce from 'rzp/utils/debounce';
import { openModal, closeModal, notifySuccess } from 'common/modal';
import Form from 'ui/Form';
import Field, {
  TextAreaField,
  SwitchField,
  SearchableSelectField,
} from 'ui/Field';
import { ModalContent } from 'component/Modal';
import JSONEdit from 'admin/razorx/JSONEdit';

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
      return 'description must be non-empty string';
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
      return 'feature id must be a valid no.';
    }
  },
  segments: function(val) {
    if (!val || !(val instanceof Array || !val.length)) {
      return 'segments must be a non-empty array';
    }

    let errorMsg;

    val.forEach(s => {
      const isVariantInvalid = !s.variant || typeof s.variant !== 'string';
      if (isVariantInvalid) {
        errorMsg = 'variant must be a non-empty string';
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
        errorMsg = 'Invalid array of ids';
        return false;
      }

      const isWeightInvalid =
        !s.weight ||
        typeof s.weight !== 'number' ||
        s.weight < 1 ||
        s.weight > 100000;
      if (isWeightInvalid) {
        errorMsg = 'weight must be a valid no. between [1-100000]';
        return false;
      }
    });

    return errorMsg;
  },
};

export default class extends React.Component {
  state = { featuresList: [] };

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
    this.setState({ featuresList: [] });
  }

  debounce_searchInFeatureList = debounce(
    this.searchInFeatureList.bind(this),
    50
  );

  onInput = e => {
    const target = e.target;

    setTimeout(() => {
      const val = target.value;

      console.log('....VAL...', val);

      if (val.length < 2) {
        this.setState({ featuresList: null });
        return;
      }

      this.debounce_searchInFeatureList(val);
    }, 5);
  };

  render() {
    const { id, name, JSONView } = this.props;

    return (
      <ModalContent
        class="modal-experiments modal-json-edit"
        header={id ? `Edit Experiment – ${name}` : 'Create Experiment'}
      >
        <Form onSubmit={this.onSubmit} class="full-span full-elements">
          {JSONView ? (
            <JSONEdit initialJSON={initJSONObj} validatorJSON={validatorJSON} />
          ) : (
            <React.Fragment>
              <SwitchField
                name="mode"
                label="Mode"
                defaultValue="live"
                disabledLabel="Test"
                enabledLabel="Live"
                enabledValue="live"
                disabledValue="test"
              />
              <Field
                label="Environment"
                type="text"
                name="environment"
                defaultValue="production"
                required
              />

              <TextAreaField
                label="Description"
                name="description"
                defaultValue=""
                required
              />

              <SearchableSelectField
                label="Feature"
                trackBy="value"
                name="feature_id"
                defaultValue=""
                onInput={this.onInput}
                options={Object.keys(this.state.featuresList).map(key => ({
                  name: this.state.featuresList[key],
                  value: key,
                }))}
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
