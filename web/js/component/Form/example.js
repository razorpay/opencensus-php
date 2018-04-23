import Form from 'component/Form';
import Field from 'component/Field'

export default class FormExample extends React.PureComponent {
  render() {
    return <Form onSubmit={submit}>
      <Field.Radio options={[
          'foo',
          'bar'
        ]}/>
      <Field name="text" pattern="[a-z]" />
    </Form>
  }
}

function submit(data) {

}
