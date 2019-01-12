import { openModal, closeModal, notifySuccess } from 'common/modal';
import Form from 'ui/Form';
import Field, { CheckField } from 'ui/Field';
import { ModalContent } from 'component/Modal';
import JSONEdit from 'admin/razorx/JSONEdit';

const initJSON = {
  description: '',
  environment: 'beta',
  mode: 'test',
  created_by: '',
  feature_id: 12,
  segments: [
    {
      variant: '',
      type: '// Eg: whitelist, blacklist, ramp, context-ramp',
      ids: [],
      weight: '// Eg: 1(=> 0.001%)',
    },
  ],
};

export default class extends React.Component {
  onSubmit = data => {};

  isValid() {
    if (this.props.JSONView) {
      // Check if JSON is valid and all required params are there
    } else {
      // Check if all required params are there
    }

    return true;
  }

  render() {
    const { id, name, JSONView } = this.props;

    return (
      <ModalContent
        class="modal-experiments modal-json-edit"
        header={id ? `Edit Experiment – ${name}` : 'Create Experiment'}
      >
        <Form onSubmit={this.onSubmit}>
          {JSONView && <JSONEdit initialJSON={initJSON} />}
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
