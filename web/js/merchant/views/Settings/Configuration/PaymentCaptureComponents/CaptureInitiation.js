import ModalHeader from 'common/ui/ModalHeader';

function CaptureInitiation(props) {
  return (
    <div class="capture-settings">
      <ModalHeader
        title={props.header}
        onCloseClick={() => {
          props.closeModal();
        }}
      />
      <div
        class={`content ${
          props.header === 'Capture Settings'
            ? 'content-small'
            : 'content-medium'
        }`}
      >
        <div class="section">
          <div
            class={`section-action ${
              props.header === 'Capture Settings' ? '' : 'margin-top-20'
            }`}
          >
            <input type="radio" />
          </div>
          <div class="section-content">
            <div class="title">{props.sectionTitles[0]}</div>
            <div class="description">{props.sectionDescriptions[0]}</div>
          </div>
        </div>
        <div class="section">
          <div
            class={`section-action ${
              props.header === 'Capture Settings' ? '' : 'margin-top-2'
            }`}
          >
            <input type="radio" />
          </div>
          <div class="section-content">
            <div class="title">{props.sectionTitles[1]}</div>
            <div class="description">{props.sectionDescriptions[1]}</div>
          </div>
        </div>
        <div
          class={`${
            props.header === 'Capture Settings' ? 'actions' : 'dual-actions'
          }`}
        >
          {props.header !== 'Capture Settings' && (
            <button class="btn btn-default" onClick={() => {}}>
              Back
            </button>
          )}
          <button class="btn btn-primary" onClick={props.handleDone}>
            Done
          </button>
        </div>
      </div>
    </div>
  );
}

export default CaptureInitiation;
