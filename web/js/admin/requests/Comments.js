import React, { Component } from 'react';
import { observer } from 'mobx-react';
import Form from 'ui/Form';
import { formatDate } from 'util/index';
import { TextAreaField } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';
import { adminPost } from 'util/fetch';

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
      route_name: 'action_comment_create',
      url_params: { id: this.props.id },
      body: { comment: this.state.comment },
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
      <div class="box comments-container">
        <div class="heading">
          <b>Comments:</b>
          <span class="pull-right">{`${comments.length} comment${
            comments.length > 1 ? 's' : ''
          }`}</span>
        </div>
        <div class="comment-list">
          {cards.length
            ? cards.map((card, idx) => {
                return card.type === 'comment' ? (
                  <div key={idx} class="comment">
                    <label class="box-label">
                      <strong>{`${card.admin.name}`}</strong>
                      <span class="secondary-label">{` commented at ${formatDate(
                        card.created_at
                      )}`}</span>
                    </label>
                    <div class="comment-body m-t">{card.comment}</div>
                  </div>
                ) : (
                  <div key={idx} class="comment">
                    <label class="box-label">
                      <i class={card.approved ? 'i i-yes' : 'i i-no'} />
                      <strong>&nbsp;{`${card.admin.name}`}</strong>
                      <span class="secondary-label">
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
        <Form class="full-span" onSubmit={this.addComment}>
          <TextAreaField
            name="comment"
            label="Add comment:"
            value={this.state.comment}
            onChange={this.handleCommentChange}
          />
          <AsyncButton
            text="Comment"
            class="btn pull-right"
            pendingClass="small spinner pull-right"
            onSubmit={this.addComment}
            disabled={!this.state.comment.length}
          />
        </Form>
      </div>
    );
  }
}
