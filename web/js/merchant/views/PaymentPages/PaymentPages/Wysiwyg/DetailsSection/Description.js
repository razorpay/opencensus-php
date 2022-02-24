import React from 'react';
import { connect } from 'react-redux';
import { classList } from 'common/utils/rzp-utils';
import debounce from 'common/utils/debounce';
import { showNotification } from 'merchant_common/reducers/notifications';
// eslint-disable-next-line import/no-named-as-default
import Popover, { PopoverBody } from 'common/ui/Popover';

import { uploadImageInDescription } from '../../model';
import { validateEmbeddedVideoUrl, isUrlLenient } from 'common/utils/validators';
import track from '../track/';

const FILE_SIZE_LIMIT = 2; // 2MB limit
const COLORS_LIST = [
  '#212121',
  '#00bb55',
  '#528ff0',
  '#f05150',
  '#ff9800',
  '#ba68c8',
  '#f06292',
  '#a1887f',
  '#58666e',
  '#b4babd',
];

const QUILL_OPTIONS = {
  formats: ['header', 'color', 'bold', 'italic', 'underline', 'list', 'link', 'image', 'video'],
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
  scrollingContainer: 'body',
};

@connect(null, { showNotification })
export default class WysiwygDescription extends React.PureComponent {
  state = { isFocused: false };

  constructor(props) {
    super(props);

    this.loadStep = 0;

    // Quill is default loaded from external script wysiwyg.js
    window.onQuillLoad = () => {
      this.loadStep++;
      this.safeInitDescription();
    };
  }

  componentDidMount() {
    this.loadStep++;

    if (window.Quill) {
      this.loadStep++;
    }

    this.safeInitDescription();
  }

  safeInitDescription() {
    // If Quill not present, then initDescription, only after both DOM and quill are loaded.
    if (this.loadStep === 2) {
      try {
        this.initDescription();
      } catch (error) {
        // if quill throws error due to delay in DOM container rendering, executing again with significant delay
        setTimeout(() => this.initDescription(), 500);
      }
    }
  }

  componentWillUnmount() {
    this.QUILL = null;
  }

  UNSAFE_componentWillUpdate(nextProps) {
    if (this.props.description !== nextProps.description && !this.props.isPageDirty && this.QUILL) {
      if (nextProps.description) {
        this.QUILL.setContents(JSON.parse(nextProps.description).value);
      } else {
        this.QUILL.setText('');
      }
    }
  }

  initDescription() {
    customizeIcons();
    this.QUILL = new window.Quill('#description-quill', QUILL_OPTIONS);

    /* Pre-fill description */
    if (this.props.description) {
      this.QUILL.setContents(JSON.parse(this.props.description).value);
    }

    /* Update description via debounce */
    this.QUILL.on('text-change', (delta, oldDelta, source) => {
      if (source == 'user') {
        this.updateDescription();
      }
    });

    /* For style handling */
    this.QUILL.on('selection-change', (range) => {
      if (!range) {
        this.setState({ isFocused: false });
      } else {
        this.setState({ isFocused: true });
      }
    });

    /* Custom Image handling */
    this.QUILL.getModule('toolbar').addHandler('image', () => this.handleImageInsert());

    this.addHookForUrlValidation();

    /* Fix keyboard bindings */
    const keyboard = this.QUILL.getModule('keyboard');
    for (const key in keyboard.hotkeys) {
      if (Object.prototype.hasOwnProperty.call(keyboard.hotkeys, key)) {
        delete keyboard.hotkeys[key];
      }
    }

    const bodyEditor = document.getElementById('description-quill');

    // Allow only certain hotkeys. Quilljs is adding hotkeys for unused modules, hence explicit handling.
    bodyEditor.addEventListener('keydown', (e) => {
      let ret = true;

      if (e.ctrlKey || e.metaKey) {
        switch (e.keyCode) {
          case 66: // ctrl+B or ctrl+b
          case 98:
            ret = false;
            break;
          case 73: // ctrl+I or ctrl+i
          case 105:
            ret = false;
            break;
          case 85: // ctrl+U or ctrl+u
          case 117:
            ret = false;
            break;
          default:
        }
      }
      return ret;
    });
  }

  addHookForUrlValidation() {
    const tooltipSave = this.QUILL.theme.tooltip.save;

    this.QUILL.theme.tooltip.save = function save() {
      // overwrite save link functionality
      let url = this.textbox.value;

      if (url.indexOf('http') === -1) {
        url = `https://${url}`;
      }

      // validate url according to mode type
      if (this.root && this.root.getAttribute) {
        const isLink = this.root.getAttribute('data-mode') === 'link';
        const isVideo = this.root.getAttribute('data-mode') === 'video';

        if (isLink && isUrlLenient(url)) {
          tooltipSave.call(this);
        } else if (isVideo) {
          if (validateEmbeddedVideoUrl(url)) {
            tooltipSave.call(this);

            track.wysiwyg.addVideoSuccess();
          } else {
            track.wysiwyg.addVideoFail(url);
          }
        }
      }
    };
  }

  handleImageInsert() {
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
        this.props.showNotification({
          type: 'success',
          message: 'Uploading image...',
          closeTimeout: 2500,
        });

        uploadImageInDescription(file)
          .then((res) => {
            if (res && res.success) {
              const url = res.data[0];

              self.QUILL.insertEmbed(range.index, 'image', url, 'user');

              track.wysiwyg.addImageSuccess();
            } else {
              const errorMessage = 'Some network error occurred';

              track.wysiwyg.addImageFail(errorMessage);

              throw new Error({ errors: [errorMessage] });
            }
          })
          .catch(({ errors }) => {
            track.wysiwyg.addImageFail(errors[0]);

            self.props.showNotification({
              type: 'error',
              message: errors[0],
            });
          });
      } else {
        const errorMessage = 'Select a valid image';

        track.wysiwyg.addImageFail(errorMessage);

        self.props.showNotification({
          type: 'error',
          message: errorMessage,
        });
      }
    };
  }

  updateDescription() {
    const desc = this.QUILL.getContents();
    let descMetaText = this.QUILL.getText(); // To consume for SEO
    descMetaText = descMetaText.replace(/(#)/gm, '').replace(/(\r\n|\n|\r)/gm, '. ');

    this.props.updateData({
      target: {
        name: 'description',
        value: JSON.stringify({ value: desc.ops, metaText: descMetaText }),
      },
    });
  }

  updateDescription = debounce(this.updateDescription, 200);

  render() {
    return (
      <div id="description" class={classList(this.state.isFocused && 'is-focused')}>
        <span class="help-content">
          <div id="description-container">
            <span id="description-quill" />
          </div>
          <Popover align="right" theme="dark">
            <PopoverBody>Give your customers more information about this page</PopoverBody>
          </Popover>
        </span>
      </div>
    );
  }
}

function customizeIcons() {
  const icons = window.Quill.import('ui/icons');

  icons.bold = '<i class="i i-bold" />';
  icons.italic = '<i class="i i-italics" />';
  icons.underline = '<i class="i i-underline" />';
  icons.link = '<i class="i i-link" />';
  icons.image = '<i class="i i-image" />';
  icons.video = '<i class="i i-video" />';
  icons.list.bullet = '<i class="i i-ul-list" />';
  icons.list.ordered = '<i class="i i-ol-list" />';
}
