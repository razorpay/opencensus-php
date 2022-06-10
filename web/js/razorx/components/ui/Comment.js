import React from 'react';
import { formatEpochDate } from 'razorx/helpers/utils';

export default function Comment(props) {
  const { commentHistory } = props;

  return (
    <details>
      <summary className="comment-title">Comments</summary>
      {commentHistory?.map((item) => {
        if (item?.comment?.trim() !== '') {
          return (
            <div key={item.id} className="comment-body">
              "{item?.comment}"
              <br />
              <span className="comment-footer">
                by {item?.actor_meta?.email} at {formatEpochDate(item?.created_at)}
              </span>
            </div>
          );
        }
        return true;
      })}
    </details>
  );
}
