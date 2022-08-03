import React from 'react';
import ModalHeader from 'common/ui/ModalHeader';
import * as ModalActions from 'merchant_common/reducers/modals';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

function ViewComments({ comments, closeModal }) {
  const websiteComments = comments.filter((item) => item.type === 'website');
  const appstoreComments = comments.filter((item) => item.type === 'appstore');
  const playstoreComments = comments.filter((item) => item.type === 'playstore');

  return (
    <div className="website-app-details-container">
      <div className="view-nc-comments-container">
        <ModalHeader title="Update your details as per the instructions below:" />
        <div className="content">
          {websiteComments.length ? (
            <div>
              <p className="title">Website</p>
              <div className="comment">
                {websiteComments.map((comment, idx) => {
                  return <span key={`${comment}_${idx}`}>{comment.reason_code}</span>;
                })}
              </div>
            </div>
          ) : null}
          <br />
          {appstoreComments.length ? (
            <div>
              <p className="title">Appstore</p>
              <div className="comment">
                {appstoreComments.map((comment, idx) => {
                  return <span key={`${comment}_${idx}`}>{comment.reason_code}</span>;
                })}
              </div>
            </div>
          ) : null}
          <br />
          {playstoreComments.length ? (
            <div>
              <p className="title">Playstore</p>
              <div className="comment">
                {playstoreComments.map((comment, idx) => {
                  return <span key={`${comment}_${idx}`}>{comment.reason_code}</span>;
                })}
              </div>
            </div>
          ) : null}
          <br />
        </div>
        <div className="actions">
          <button className="btn btn-primary" onClick={closeModal}>
            Okay, got it
          </button>
        </div>
      </div>
    </div>
  );
}

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      ...ModalActions,
    },
    dispatch,
  );

export default connect(null, mapDispatchToProps)(ViewComments);
