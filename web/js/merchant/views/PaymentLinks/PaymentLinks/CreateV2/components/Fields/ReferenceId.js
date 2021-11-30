import { connect } from 'react-redux';

import Input from 'common/new-ui/Input';
import track from '../../track';

const ReferenceId = (props) => (
  <Input
    name="reference_id"
    placeholder="123456"
    label="Reference Id"
    class="Input--vTop"
    labelClass="Input-label pb-8"
    required={props.required}
    disabled={props.disabled}
    onBlur={() => {
      track.lj.fields.receipt();
      track.segment.fields.receipt();
    }}
  />
);

const mapStateTopProps = (state) => ({
  required: state.session.user.isInvoiceReceiptMandatory,
});
export default connect(mapStateTopProps)(ReferenceId);
