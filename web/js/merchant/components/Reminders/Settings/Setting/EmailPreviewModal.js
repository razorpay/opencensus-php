import ModalHeader from 'rzp/ui/ModalHeader';

export default ({ subject, contentList, businessName, onClose }) => (
  <div class="modal-container">
    <ModalHeader title="Email Preview" onCloseClick={onClose} />
    <div class="preview">
      <h4 class="title"> Subject: {subject} </h4>
      Hi there, <br /> <br />
      {contentList.map((content, idx) => <p key={idx}>{content}</p>)}
      <div class="footer">
        Regards, <br />
        {businessName}
      </div>
    </div>
  </div>
);
