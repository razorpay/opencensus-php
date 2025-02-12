import React, { Component } from 'react';
import { observer } from 'mobx-react';
import Form from 'razorx/components/ui/Form';
import { formatDate } from 'razorx/helpers/utils';
import { TextAreaField } from 'razorx/components/ui/Field';
import AsyncButton from 'razorx/components/ui/AsyncButton';
import { adminPost } from 'razorx/helpers/admin-fetch';

@observer
export default class Comments extends Component {
  state = {
    comment: '',
  };

  handleCommentChange = e => {
    this.setState({ comment: e.target.value });
  };

  sortCards = () => {
    const { comments, checkers } = this.props;
    let cards = [];

    cards = [...comments.peek(), ...checkers.peek()];

    //sort cards based on created_at timepstamp(recent as last)
    cards.sort(
      (currCard, nextCard) => currCard.created_at - nextCard.created_at
    );

    return cards;
  };

  addComment = () => {
    const { onCommentAdd } = this.props;

    return adminPost({
      url: `live/w-actions/${this.props.id}/comments`,
      data: { comment: this.state.comment },
    }).then(response => {
      if (response) {
        this.setState({ comment: '' }, () => onCommentAdd(response));
      }
    });
  };

  render() {
    const cards = this.sortCards();
    const { comments } = this.props;

    return (
      <div className="box comments-container">
        <div className="heading">
          <b>Comments:</b>
          <span className="pull-right">{`${comments.length} comment${
            comments.length > 1 ? 's' : ''
          }`}</span>
        </div>
        <div className="comment-list">
          {cards.length
            ? cards.map((card, idx) => {
                return card.type === 'comment' ? (
                  <div key={idx} className="comment">
                    <label className="box-label">
                      <strong>{`${card.admin.name}`}</strong>
                      <span className="secondary-label">{` commented at ${formatDate(
                        card.created_at
                      )}`}</span>
                    </label>
                    <div className="comment-body m-t">{card.comment}</div>
                  </div>
                ) : (
                  <div key={idx} className="comment">
                    <label className="box-label">
                      <i
                        className={
                          card.approved
                            ? 'i i-yes text-success'
                            : 'i i-no text-danger'
                        }
                      />
                      <strong>&nbsp;{`${card.admin.name}`}</strong>
                      <span className="secondary-label">
                        {` ${
                          card.approved ? 'approved' : 'rejected'
                        } at ${formatDate(card.created_at)}`}
                      </span>
                    </label>
                  </div>
                );
              })
            : null}
        </div>
        <Form className="full-span" onSubmit={this.addComment}>
          <TextAreaField
            name="comment"
            label="Add comment:"
            value={this.state.comment}
            onChange={this.handleCommentChange}
          />
          <AsyncButton
            text="Comment"
            className="btn pull-right"
            pendingClass="small spinner pull-right"
            onSubmit={this.addComment}
            disabled={!this.state.comment.length}
          />
        </Form>
      </div>
    );
  }
}
