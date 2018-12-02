import { Component } from 'react';

import Form from 'ui/Form';
import Field, { SelectField } from 'ui/Field';

export default class AddCustomNote extends Component {
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
          <SelectField name="field" label="Select Entity">
            <option value="">Select...</option>
            {this.props.fields.map(field => (
              <option key={field} value={field}>
                {field}
              </option>
            ))}
          </SelectField>

          <Field label="Custom field name" name="subColumn" />
          <button class="btn" type="submit">
            Save
          </button>
        </Form>
      </div>
    );
  }
}
