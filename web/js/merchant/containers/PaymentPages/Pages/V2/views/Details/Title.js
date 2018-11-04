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
    this.elHeight = fakeEle.scrollHeight + 10 + 'px'; // 6 is the vertical padding(top+bottom) size of the textarea in css
    target.style.height = this.elHeight;
  }

  componentDidMount() {
    this.autoAdjustHeight(
      document.body.querySelector('#title textarea[name="title"]')
    );
  }

  render() {
    return (
      <div id="title" class="title title--big">
        <div class="fake-textarea" />
        <Input.Textarea
          name="title"
          placeholder="Enter page title here"
          info="This is the heading of your page. Help your customers recognise the page with this"
          defaultValue={this.props.title}
          onInput={this.handleOnInput}
          onBlur={this.props.updateData}
        />
        <div class="title-underline" />
      </div>
    );
  }
}
