import { Component } from 'react';

import Form from 'ui/Form';
import Field from 'ui/Field';

export default class AddConstantField extends Component {
  state = {};

  handleChangeIn = ({ target }) => {
    const { name, value } = target;
    this.setState({
      [name]: value,
    });
  };

  handleSaveClick = () => {
    this.props.onSave(this.state);
  };

  render() {
    return (
      <div>
        <Form onChange={this.handleChangeIn} onSubmit={this.handleSaveClick}>
          <Field name="field" label="Constant Value" required />

          <button className="btn" type="submit">
            Save
          </button>
        </Form>
      </div>
    );
  }
}
