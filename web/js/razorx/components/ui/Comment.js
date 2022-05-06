import React from 'react';
import { formatDate, formatEpochDate } from 'razorx/helpers/utils';

export default function Comment(props) {
  const { submittedComment, commentDate, commentWriter } = props;

  return (
    <details>
      <summary className="comment-title">Comments</summary>
      <div className="comment-body">
        "{submittedComment}"
        <br />
        <span className="comment-footer">
          by {commentWriter} at {formatDate(formatEpochDate(commentDate))}
        </span>
      </div>
    </details>
  );
}
