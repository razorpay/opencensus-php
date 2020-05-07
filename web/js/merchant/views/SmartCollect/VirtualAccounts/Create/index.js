import { connect } from 'react-redux';

import CreateV1 from './CreateV1';
import CreateV2 from './CreateV2';

const CreateVirtualAccountWrapper = props =>
  props.user.isVirtualVPAPrefixEnabled ? (
    <CreateV2 {...props} />
  ) : (
    <CreateV1 {...props} />
  );

export default connect(state => ({ user: state.session.user }))(
  CreateVirtualAccountWrapper
);
