import { classList } from 'common/util';
import Input from 'component/Input';

export default class extends React.Component {
  handleOnInput = ({ target }) => {
    this.autoAdjustHeight(target);
  };

  autoAdjustHeight(target) {
    if (!target) {
      return;
    }

    const content = target.value;
    const fakeEle = window.document.querySelector('#title .fake-textarea');

    fakeEle.innerHTML = content;
    this.elHeight = fakeEle.scrollHeight + 10 + 'px'; // 10 is combination of vertical padding and line height of the textarea in css
    target.style.height = this.elHeight;
  }

  componentDidMount() {
    this.autoAdjustHeight(
      document.body.querySelector('#title textarea[name="title"]')
    );
  }

  render() {
    const ele = document.body.querySelector('#title textarea[name="title"]');
    const hasVal = ele ? ele.value : this.props.title;

    return (
      <div
        id="title"
        class={classList('title title--big', !hasVal && 'Input-highlight')}
      >
        <div class="fake-textarea" />
        <Input.Textarea
          name="title"
          placeholder="Enter page title here"
          info="This is the heading of your page. Help your customers recognise the page with this"
          defaultValue={this.props.title}
          onInput={this.handleOnInput}
          onBlur={this.props.updateData}
          maxLength="40"
        />
        <div class="title-underline" />
      </div>
    );
  }
}
