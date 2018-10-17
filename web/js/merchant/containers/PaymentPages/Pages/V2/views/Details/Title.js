import Input from 'component/Input';
import EditLayer from '../EditLayer';

export default class extends React.PureComponent {
  render() {
    return (
      <div class="title title--big">
        <Input
          name="title"
          placeholder="Enter page title here"
          info="This is the heading of your page. Help your customers recognise the page with this"
          defaultValue={this.props.title}
          autoFocus
          onBlur={this.props.updateData}
        />
        <div class="title-underline" />
      </div>
    );
  }
}
