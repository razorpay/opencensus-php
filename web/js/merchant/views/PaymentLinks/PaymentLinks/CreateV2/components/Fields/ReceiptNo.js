import { connect } from 'react-redux';

import Input from 'common/new-ui/Input';

const ReceiptNo = (props) => (
  <Input
    name="reference_id"
    placeholder="123456"
    label="Receipt No"
    class="Input--vTop"
    {...props}
  />
);

const mapStateTopProps = (state) => ({
  required: state.session.user.isInvoiceReceiptMandatory,
});
export default connect(mapStateTopProps)(ReceiptNo);
