import { classList } from 'common/util';
import debounce from 'rzp/utils/debounce';

const COLORS_LIST = [
  '#00BB55',
  '#528FF0',
  '#F05150',
  '#FF9800',
  '#BA68C8',
  '#F06292',
  '#A1887F',
  '#58666E',
  '#B4BABD',
];

const QUILL_OPTIONS = {
  modules: {
    toolbar: [
      [{ header: [2, 3, false] }],
      [{ color: COLORS_LIST }, 'bold', 'italic', 'underline'],
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
      customizeIcons();
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

function customizeIcons() {
  const icons = window.Quill.import('ui/icons');

  icons['color'] = '<i class="i i-text-color" />';
  icons['bold'] = '<i class="i i-bold" />';
  icons['italic'] = '<i class="i i-italics" />';
  icons['underline'] = '<i class="i i-underline" />';
  icons['link'] = '<i class="i i-link" />';
  icons['image'] = '<i class="i i-image" />';
  icons['video'] = '<i class="i i-video" />';
  icons['list']['bullet'] = '<i class="i i-ul-list" />';
  icons['list']['ordered'] = '<i class="i i-ol-list" />';
}
