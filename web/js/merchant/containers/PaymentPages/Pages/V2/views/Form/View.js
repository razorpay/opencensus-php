import { connect } from 'react-redux';
import {
  deleteInSchema,
  updateInSchema,
  addInSchema,
} from 'merchant/modules/wysiwyg';

@connect(state => ({ FORM_SCHEMA: state.wysiwyg.FORM_SCHEMA }), {
  deleteInSchema,
  updateInSchema,
  addInSchema,
})
export default class View extends React.PureComponent {
  render() {
    return 'Hello World';
  }
}
