import Input from 'common/new-ui/Input';

const Notes = (props) => {
  return <Input.PairList autoRender class="Input--vTop" name="notes" label="Notes" {...props} />;
};

export default Notes;
