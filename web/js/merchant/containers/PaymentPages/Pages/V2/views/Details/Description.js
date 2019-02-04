import { classList } from 'common/util';
import debounce from 'rzp/utils/debounce';

const QUILL_OPTIONS = {
  modules: {
    toolbar: [
      [{ header: [2, 3, false] }],
      [{ color: ['#000', '#8a4'] }, 'bold', 'italic', 'underline'],
      [{ list: 'bullet' }, { list: 'ordered' }],
      ['link', 'image', 'video'],
    ],
  },
  placeholder: 'Enter page description',
  theme: 'snow',
};

// BEWARE: Don't remove whitespaces from infoTxt.
const infoTxt = `Give your customers more information about this page.

Note:
All URLs will convert to links.`;

export default class extends React.PureComponent {
  state = { isScriptLoaded: null };
  componentDidMount() {
    window.onQuillLoad = () => {
      this.QUILL = new window.Quill('#description-quill', QUILL_OPTIONS);

      this.props.description &&
        this.QUILL.setContents(JSON.parse(this.props.description));

      this.QUILL.on('text-change', (delta, oldDelta, source) => {
        if (source == 'user') {
          this.updateDescription();
        }
      });
    };
  }

  componentWillUpdate(nextProps) {
    if (this.props.description !== nextProps.description) {
      this.QUILL && this.QUILL.setContents(JSON.parse(nextProps.description));
    }
  }

  updateDescription() {
    const desc = this.QUILL.getContents();
    this.props.updateData({
      target: { name: 'description', value: JSON.stringify(desc.ops) },
    });
  }

  updateDescription = debounce(::this.updateDescription, 200);

  render() {
    return (
      <div id="description">
        <div id="description-quill" />
      </div>
    );
  }
}
