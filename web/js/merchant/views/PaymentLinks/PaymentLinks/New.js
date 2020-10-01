import { connect } from 'react-redux';

// TODO: Rename this file to Create and rename Create folder into CreateV1
import CreateV1 from './Create';
import CreateV2 from './CreateV2';

const CreatePaymentLinkWrapper = (props) =>
  props.user.isPaymentLinkCreationV2Enabled ? <CreateV2 {...props} /> : <CreateV1 {...props} />;

export default connect((state) => ({ user: state.session.user }))(CreatePaymentLinkWrapper);
