import { connect } from 'react-redux';
import { classList } from 'common/util';
import debounce from 'rzp/utils/debounce';
import { showNotification } from 'rzp/modules/notifications';

const FILE_SIZE_LIMIT = 10;
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

@connect(null, { showNotification })
export default class extends React.PureComponent {
  state = { isScriptLoaded: null };
  imagesRangeIndex = {};

  componentDidMount() {
    window.onQuillLoad = () => {
      customizeIcons();
      this.QUILL = new window.Quill('#description-quill', QUILL_OPTIONS);

      this.props.description &&
        this.QUILL.setContents(JSON.parse(this.props.description));

      this.QUILL.on('text-change', (delta, oldDelta, source) => {
        console.log('DELTA...', delta);
        if (source == 'user') {
          if (delta.ops) {
            const isDeleteOp =
              delta.ops[1] && delta.ops[1].hasOwnProperty('delete');
            const opOnIndex = delta.ops[0].retain;

            if (isDeleteOp && this.imagesRangeIndex.hasOwnProperty(opOnIndex)) {
              delete this.imagesRangeIndex[opOnIndex];
            }
          }
        }

        if (['user', 'api_img'].indexOf(source) > -1) {
          this.updateDescription();
        }
      });

      this.QUILL.getModule('toolbar').addHandler('image', () =>
        this.handleImageInsert()
      );
    };
  }

  componentWillUpdate(nextProps) {
    if (
      this.props.description !== nextProps.description &&
      !this.templateInitDone
    ) {
      this.templateInitDone = true;
      this.QUILL && this.QUILL.setContents(JSON.parse(nextProps.description));
    }
  }

  handleImageInsert(f) {
    const self = this;
    const range = self.QUILL.getSelection();

    // Listen upload local image and save to server
    const input = document.createElement('input');
    input.setAttribute('type', 'file');
    input.click();

    input.onchange = () => {
      const file = input.files[0];
      const fileSizeMB = file.size / 1024 / 1024;

      if (fileSizeMB > FILE_SIZE_LIMIT) {
        self.props.showNotification({
          type: 'error',
          message: `Image too large. Max limit ${FILE_SIZE_LIMIT}MB`,
        });

        return;
      }

      const isImageType = /^image\//.test(file.type);

      if (isImageType) {
        self.imagesRangeIndex[range.index] = file;

        const reader = new FileReader();

        reader.onload = function(e) {
          const base64Img = e.target.result;
          self.QUILL.insertEmbed(range.index, 'image', base64Img, 'api_img');
        };

        reader.readAsDataURL(file);
      } else {
        self.props.showNotification({
          type: 'error',
          message: 'Select a valid Image',
        });

        return;
      }
    };
  }

  updateDescription() {
    const desc = this.QUILL.getContents();
    this.props.updateData({
      target: { name: 'description', value: desc.ops },
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

  icons['bold'] = '<i class="i i-bold" />';
  icons['italic'] = '<i class="i i-italics" />';
  icons['underline'] = '<i class="i i-underline" />';
  icons['link'] = '<i class="i i-link" />';
  icons['image'] = '<i class="i i-image" />';
  icons['video'] = '<i class="i i-video" />';
  icons['list']['bullet'] = '<i class="i i-ul-list" />';
  icons['list']['ordered'] = '<i class="i i-ol-list" />';
}
