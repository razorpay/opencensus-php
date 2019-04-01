import Form from 'ui/Form';
import Field from 'ui/Field';

export default function ListFitler(props) {
  return (
    <div className="box">
      <Form onSubmit={props.onFilterSubmit} class="filters">
        <Field name="id" label="Submerchant ID" />

        <Field name="email" label="Submerchant Email" />

        <button>Search</button>
      </Form>
    </div>
  );
}
